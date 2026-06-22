<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\DeferredCreateInstanceDispatcherInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StartInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StopInstanceHandlerInterface;
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

    /** @var CreateInstanceHandlerInterface */
    private $createHandler;

    /** @var StopInstanceHandlerInterface */
    private $stopHandler;

    /** @var StartInstanceHandlerInterface */
    private $startHandler;

    /** @var IdempotencyStoreInterface */
    private $idempotencyStore;

    /** @var OrchestrationCommandLifecycleStoreInterface */
    private $lifecycleStore;

    /** @var AmApiClientInterface */
    private $amApiClient;

    /** @var DeferredCreateInstanceDispatcherInterface */
    private $deferredDispatcher;

    /** @var string */
    private $createInstanceExecution;

    public function __construct(
        CreateInstanceHandlerInterface $createHandler,
        StopInstanceHandlerInterface $stopHandler,
        StartInstanceHandlerInterface $startHandler,
        IdempotencyStoreInterface $idempotencyStore,
        AmApiClientInterface $amApiClient,
        OrchestrationCommandLifecycleStoreInterface $lifecycleStore,
        DeferredCreateInstanceDispatcherInterface $deferredDispatcher,
        string $createInstanceExecution = self::CREATE_INSTANCE_EXECUTION_SYNC
    ) {
        $this->createHandler = $createHandler;
        $this->stopHandler = $stopHandler;
        $this->startHandler = $startHandler;
        $this->idempotencyStore = $idempotencyStore;
        $this->amApiClient = $amApiClient;
        $this->lifecycleStore = $lifecycleStore;
        $this->deferredDispatcher = $deferredDispatcher;
        $this->createInstanceExecution = $createInstanceExecution;
    }

    /**
     * @return array{httpStatus: int, alreadyProcessed: bool}
     */
    public function process(OrchestrationCommand $command): array
    {
        if ($this->idempotencyStore->has($command->idempotencyKey())) {
            return ['httpStatus' => 200, 'alreadyProcessed' => true];
        }

        if ($command->operation()->isCreate() && self::CREATE_INSTANCE_EXECUTION_DEFERRED === $this->createInstanceExecution) {
            return $this->acceptCreateInstanceDeferred($command);
        }

        $createResult = null;
        try {
            if ($command->operation()->isCreate()) {
                $createResult = $this->createHandler->handle($command);
            } elseif ($command->operation()->isStop()) {
                $this->stopHandler->handle($command);
            } elseif ($command->operation()->isStart()) {
                $this->startHandler->handle($command);
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
            $createResult instanceof CreateInstanceHandlerResult ? $createResult->instanceLocation() : null,
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
                $createResult->instanceLocation(),
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
        $this->deferredDispatcher->dispatch($command);

        return ['httpStatus' => 200, 'alreadyProcessed' => false];
    }

    private function reportCallback(
        OrchestrationCommand $command,
        CallbackStatus $status,
        ?string $message,
        ?string $location = null
    ): void {
        $this->amApiClient->reportOrchestrationCallback(
            new OrchestrationCallbackRequest($command->idempotencyKey(), $status, $message, $location),
        );
    }
}
