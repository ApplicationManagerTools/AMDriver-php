<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\DeferredCreateInstanceDispatcherInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\DeferredUpgradeInstanceDispatcherInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\GetInfoInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\QuotaExceededInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\SetStateViewInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StartInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StopInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\UpgradeInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\CreateInstanceHandlerResult;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\HandlerFailedException;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientInterface;
use ApplicationManagerTools\AmDriver\Core\Idempotency\IdempotencyStoreInterface;
use ApplicationManagerTools\AmDriver\Core\Idempotency\OrchestrationCommandLifecycleStoreInterface;
use Throwable;

final class OrchestrationCommandProcessor
{
    public const CREATE_INSTANCE_EXECUTION_SYNC = 'sync';
    public const CREATE_INSTANCE_EXECUTION_DEFERRED = 'deferred';
    public const UPGRADE_INSTANCE_EXECUTION_SYNC = 'sync';
    public const UPGRADE_INSTANCE_EXECUTION_DEFERRED = 'deferred';

    /** @var CreateInstanceHandlerInterface */
    private $createHandler;

    /** @var StopInstanceHandlerInterface */
    private $stopHandler;

    /** @var StartInstanceHandlerInterface */
    private $startHandler;

    /** @var QuotaExceededInstanceHandlerInterface */
    private $quotaExceededHandler;

    /** @var GetInfoInstanceHandlerInterface */
    private $getInfoHandler;

    /** @var SetStateViewInstanceHandlerInterface */
    private $setStateViewHandler;

    /** @var UpgradeInstanceHandlerInterface */
    private $upgradeHandler;

    /** @var IdempotencyStoreInterface */
    private $idempotencyStore;

    /** @var OrchestrationCommandLifecycleStoreInterface */
    private $lifecycleStore;

    /** @var AmApiClientInterface */
    private $amApiClient;

    /** @var DeferredCreateInstanceDispatcherInterface */
    private $deferredCreateDispatcher;

    /** @var DeferredUpgradeInstanceDispatcherInterface */
    private $deferredUpgradeDispatcher;

    /** @var string */
    private $createInstanceExecution;

    /** @var string */
    private $upgradeInstanceExecution;

    public function __construct(
        CreateInstanceHandlerInterface $createHandler,
        StopInstanceHandlerInterface $stopHandler,
        StartInstanceHandlerInterface $startHandler,
        QuotaExceededInstanceHandlerInterface $quotaExceededHandler,
        GetInfoInstanceHandlerInterface $getInfoHandler,
        SetStateViewInstanceHandlerInterface $setStateViewHandler,
        UpgradeInstanceHandlerInterface $upgradeHandler,
        IdempotencyStoreInterface $idempotencyStore,
        AmApiClientInterface $amApiClient,
        OrchestrationCommandLifecycleStoreInterface $lifecycleStore,
        DeferredCreateInstanceDispatcherInterface $deferredCreateDispatcher,
        DeferredUpgradeInstanceDispatcherInterface $deferredUpgradeDispatcher,
        string $createInstanceExecution = self::CREATE_INSTANCE_EXECUTION_SYNC,
        string $upgradeInstanceExecution = self::UPGRADE_INSTANCE_EXECUTION_DEFERRED
    ) {
        $this->createHandler = $createHandler;
        $this->stopHandler = $stopHandler;
        $this->startHandler = $startHandler;
        $this->quotaExceededHandler = $quotaExceededHandler;
        $this->getInfoHandler = $getInfoHandler;
        $this->setStateViewHandler = $setStateViewHandler;
        $this->upgradeHandler = $upgradeHandler;
        $this->idempotencyStore = $idempotencyStore;
        $this->amApiClient = $amApiClient;
        $this->lifecycleStore = $lifecycleStore;
        $this->deferredCreateDispatcher = $deferredCreateDispatcher;
        $this->deferredUpgradeDispatcher = $deferredUpgradeDispatcher;
        $this->createInstanceExecution = $createInstanceExecution;
        $this->upgradeInstanceExecution = $upgradeInstanceExecution;
    }

    /**
     * @return array{httpStatus: int, alreadyProcessed?: bool, body?: array<string, mixed>}
     */
    public function process(OrchestrationCommand $command): array
    {
        if ($command->operation()->isGetInfo()) {
            return $this->processGetInfo($command);
        }
        if ($command->operation()->isSetStateView()) {
            return $this->processSetStateView($command);
        }

        if ($this->idempotencyStore->has($command->idempotencyKey())) {
            return ['httpStatus' => 200, 'alreadyProcessed' => true];
        }

        if ($command->operation()->isCreate() && self::CREATE_INSTANCE_EXECUTION_DEFERRED === $this->createInstanceExecution) {
            return $this->acceptCreateInstanceDeferred($command);
        }

        if ($command->operation()->isUpgrade() && self::UPGRADE_INSTANCE_EXECUTION_DEFERRED === $this->upgradeInstanceExecution) {
            return $this->acceptUpgradeInstanceDeferred($command);
        }

        $createResult = null;
        try {
            if ($command->operation()->isCreate()) {
                $createResult = $this->createHandler->handle($command);
            } elseif ($command->operation()->isStop()) {
                $this->stopHandler->handle($command);
            } elseif ($command->operation()->isStart()) {
                $this->startHandler->handle($command);
            } elseif ($command->operation()->isQuotaExceeded()) {
                $this->quotaExceededHandler->handle($command);
            } elseif ($command->operation()->isUpgrade()) {
                $this->upgradeHandler->handle($command);
            } elseif ($command->operation()->isDestroy()) {
                throw new ValidationException('DESTROY_INSTANCE is not supported by am-driver v1; see docs/ECARTS-AM.md');
            } else {
                throw new ValidationException('Unsupported operation');
            }
        } catch (HandlerFailedException $e) {
            $this->reportCallback($command, $e->callbackStatus(), $e->getMessage());

            if (CallbackStatus::RETRYABLE_FAILURE === $e->callbackStatus()->toString()) {
                return ['httpStatus' => 500, 'alreadyProcessed' => false];
            }

            return ['httpStatus' => 400, 'alreadyProcessed' => false];
        } catch (ValidationException $e) {
            $this->reportCallback($command, CallbackStatus::failed(), $e->getMessage());

            return ['httpStatus' => 400, 'alreadyProcessed' => false];
        } catch (Throwable $e) {
            $this->reportCallback($command, CallbackStatus::retryableFailure(), $e->getMessage());

            return ['httpStatus' => 500, 'alreadyProcessed' => false];
        }

        $this->idempotencyStore->remember($command->idempotencyKey());
        $this->reportCallback(
            $command,
            CallbackStatus::succeeded(),
            null,
            $createResult instanceof CreateInstanceHandlerResult ? $createResult->toArray() : [],
        );

        return ['httpStatus' => 200, 'alreadyProcessed' => false];
    }

    public function executeCreateInstance(OrchestrationCommand $command): void
    {
        try {
            $createResult = $this->createHandler->handle($command);
            $this->idempotencyStore->remember($command->idempotencyKey());
            $this->reportCallback(
                $command,
                CallbackStatus::succeeded(),
                null,
                $createResult->toArray(),
            );
        } catch (HandlerFailedException $e) {
            $this->reportCallback($command, $e->callbackStatus(), $e->getMessage());
            throw $e;
        } catch (ValidationException $e) {
            $this->reportCallback($command, CallbackStatus::failed(), $e->getMessage());
            throw $e;
        } catch (Throwable $e) {
            $this->reportCallback($command, CallbackStatus::retryableFailure(), $e->getMessage());
            throw $e;
        } finally {
            $this->lifecycleStore->clearInProgress($command->idempotencyKey());
        }
    }

    public function executeUpgradeInstance(OrchestrationCommand $command): void
    {
        try {
            $this->upgradeHandler->handle($command);
            $this->idempotencyStore->remember($command->idempotencyKey());
            $this->reportCallback($command, CallbackStatus::succeeded(), null);
        } catch (HandlerFailedException $e) {
            $this->reportCallback($command, $e->callbackStatus(), $e->getMessage());
            throw $e;
        } catch (ValidationException $e) {
            $this->reportCallback($command, CallbackStatus::failed(), $e->getMessage());
            throw $e;
        } catch (Throwable $e) {
            $this->reportCallback($command, CallbackStatus::retryableFailure(), $e->getMessage());
            throw $e;
        } finally {
            $this->lifecycleStore->clearInProgress($command->idempotencyKey());
        }
    }

    /**
     * @return array{httpStatus: int, body: array<string, mixed>}
     */
    private function processGetInfo(OrchestrationCommand $command): array
    {
        try {
            $resources = $this->getInfoHandler->handle($command);
        } catch (HandlerFailedException $e) {
            $status = CallbackStatus::RETRYABLE_FAILURE === $e->callbackStatus()->toString() ? 500 : 400;

            return ['httpStatus' => $status, 'body' => ['error' => $e->getMessage()]];
        } catch (ValidationException $e) {
            return ['httpStatus' => 400, 'body' => ['error' => $e->getMessage()]];
        } catch (Throwable $e) {
            return ['httpStatus' => 500, 'body' => ['error' => $e->getMessage()]];
        }

        return ['httpStatus' => 200, 'body' => ['resources' => $resources]];
    }

    /**
     * @return array{httpStatus: int, body: array<string, mixed>}
     */
    private function processSetStateView(OrchestrationCommand $command): array
    {
        if ([] === $command->stateView()) {
            return ['httpStatus' => 400, 'body' => ['error' => 'SET_STATEVIEW_INSTANCE requires a non-empty stateView']];
        }

        try {
            $this->setStateViewHandler->handle($command);
        } catch (HandlerFailedException $e) {
            $status = CallbackStatus::RETRYABLE_FAILURE === $e->callbackStatus()->toString() ? 500 : 400;

            return ['httpStatus' => $status, 'body' => ['error' => $e->getMessage()]];
        } catch (ValidationException $e) {
            return ['httpStatus' => 400, 'body' => ['error' => $e->getMessage()]];
        } catch (Throwable $e) {
            return ['httpStatus' => 500, 'body' => ['error' => $e->getMessage()]];
        }

        return ['httpStatus' => 200, 'body' => ['updated' => true]];
    }

    /**
     * @return array{httpStatus: int, alreadyProcessed: bool}
     */
    private function acceptCreateInstanceDeferred(OrchestrationCommand $command): array
    {
        if (null === $command->name() || null === $command->credentialsLogin()) {
            $this->reportCallback(
                $command,
                CallbackStatus::failed(),
                'CREATE_INSTANCE requires name and credentials.login',
            );

            return ['httpStatus' => 400, 'alreadyProcessed' => false];
        }

        if ($this->lifecycleStore->isInProgress($command->idempotencyKey())) {
            return ['httpStatus' => 200, 'alreadyProcessed' => true];
        }

        $this->lifecycleStore->markInProgress($command->idempotencyKey());
        $this->deferredCreateDispatcher->dispatch($command);

        return ['httpStatus' => 200, 'alreadyProcessed' => false];
    }

    /**
     * @return array{httpStatus: int, alreadyProcessed: bool}
     */
    private function acceptUpgradeInstanceDeferred(OrchestrationCommand $command): array
    {
        if ([] === $command->stateView()) {
            $this->reportCallback(
                $command,
                CallbackStatus::failed(),
                'UPGRADE_INSTANCE requires a non-empty stateView',
            );

            return ['httpStatus' => 400, 'alreadyProcessed' => false];
        }

        if ($this->lifecycleStore->isInProgress($command->idempotencyKey())) {
            return ['httpStatus' => 200, 'alreadyProcessed' => true];
        }

        $this->lifecycleStore->markInProgress($command->idempotencyKey());
        $this->deferredUpgradeDispatcher->dispatch($command);

        return ['httpStatus' => 200, 'alreadyProcessed' => false];
    }

    /**
     * @param array<string, mixed> $resultData données métier libres issues de CreateInstanceHandlerResult::toArray(),
     *                                         transportées telles quelles vers Application Manager
     */
    private function reportCallback(
        OrchestrationCommand $command,
        CallbackStatus $status,
        ?string $message,
        array $resultData = []
    ): void {
        $this->amApiClient->reportOrchestrationCallback(
            new OrchestrationCallbackRequest($command->idempotencyKey(), $status, $message, $resultData),
        );
    }
}
