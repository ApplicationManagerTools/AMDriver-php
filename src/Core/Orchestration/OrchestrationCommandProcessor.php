<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\ActualResourcesConsumptionReaderInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\DeferredCreateInstanceDispatcherInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\InstanceDataDirectoryResolverInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StartInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StateViewWriterInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StopInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\CreateInstanceHandlerResult;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\HandlerFailedException;
use ApplicationManagerTools\AmDriver\Core\Exception\InstanceDataDirectoryNotFoundException;
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

    /** @var InstanceDataDirectoryResolverInterface|null */
    private $instanceDataDirectoryResolver;

    /** @var ActualResourcesConsumptionReaderInterface|null */
    private $actualResourcesReader;

    /** @var StateViewWriterInterface|null */
    private $stateViewWriter;

    public function __construct(
        CreateInstanceHandlerInterface $createHandler,
        StopInstanceHandlerInterface $stopHandler,
        StartInstanceHandlerInterface $startHandler,
        IdempotencyStoreInterface $idempotencyStore,
        AmApiClientInterface $amApiClient,
        OrchestrationCommandLifecycleStoreInterface $lifecycleStore,
        DeferredCreateInstanceDispatcherInterface $deferredDispatcher,
        string $createInstanceExecution = self::CREATE_INSTANCE_EXECUTION_SYNC,
        ?InstanceDataDirectoryResolverInterface $instanceDataDirectoryResolver = null,
        ?ActualResourcesConsumptionReaderInterface $actualResourcesReader = null,
        ?StateViewWriterInterface $stateViewWriter = null
    ) {
        $this->createHandler = $createHandler;
        $this->stopHandler = $stopHandler;
        $this->startHandler = $startHandler;
        $this->idempotencyStore = $idempotencyStore;
        $this->amApiClient = $amApiClient;
        $this->lifecycleStore = $lifecycleStore;
        $this->deferredDispatcher = $deferredDispatcher;
        $this->createInstanceExecution = $createInstanceExecution;
        $this->instanceDataDirectoryResolver = $instanceDataDirectoryResolver;
        $this->actualResourcesReader = $actualResourcesReader;
        $this->stateViewWriter = $stateViewWriter;
    }

    /**
     * @param array<string, string> $queryParams
     *
     * @return array{httpStatus: int, alreadyProcessed?: bool, body?: array<string, mixed>}
     */
    public function process(OrchestrationCommand $command, array $queryParams = []): array
    {
        if ($command->operation()->isGetInfo()) {
            return $this->processGetInfo($command, $queryParams);
        }
        if ($command->operation()->isSetStateView()) {
            return $this->processSetStateView($command, $queryParams);
        }

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

    /**
     * @param array<string, string> $queryParams
     *
     * @return array{httpStatus: int, body: array<string, mixed>}
     */
    private function processGetInfo(OrchestrationCommand $command, array $queryParams): array
    {
        $deps = $this->requireSyncDeps();

        try {
            $dataDir = $deps['resolver']->resolve($command->instanceId(), $queryParams);
        } catch (InstanceDataDirectoryNotFoundException $e) {
            return ['httpStatus' => 404, 'body' => ['error' => $e->getMessage()]];
        }

        $resources = $deps['reader']->read($dataDir);

        return ['httpStatus' => 200, 'body' => ['resources' => $resources]];
    }

    /**
     * @param array<string, string> $queryParams
     *
     * @return array{httpStatus: int, body: array<string, mixed>}
     */
    private function processSetStateView(OrchestrationCommand $command, array $queryParams): array
    {
        $deps = $this->requireSyncDeps();

        if ([] === $command->stateView()) {
            return ['httpStatus' => 400, 'body' => ['error' => 'SET_STATEVIEW_INSTANCE requires a non-empty stateView']];
        }

        try {
            $dataDir = $deps['resolver']->resolve($command->instanceId(), $queryParams);
        } catch (InstanceDataDirectoryNotFoundException $e) {
            return ['httpStatus' => 404, 'body' => ['error' => $e->getMessage()]];
        }

        try {
            $deps['writer']->write($dataDir, $command->stateView());
        } catch (Throwable $e) {
            return ['httpStatus' => 500, 'body' => ['error' => $e->getMessage()]];
        }

        return ['httpStatus' => 200, 'body' => ['updated' => true]];
    }

    /**
     * @return array{
     *   resolver: InstanceDataDirectoryResolverInterface,
     *   reader: ActualResourcesConsumptionReaderInterface,
     *   writer: StateViewWriterInterface
     * }
     */
    private function requireSyncDeps(): array
    {
        if (
            null === $this->instanceDataDirectoryResolver
            || null === $this->actualResourcesReader
            || null === $this->stateViewWriter
        ) {
            throw new ValidationException('GET_INFO_INSTANCE / SET_STATEVIEW_INSTANCE require instance data directory configuration');
        }

        return [
            'resolver' => $this->instanceDataDirectoryResolver,
            'reader' => $this->actualResourcesReader,
            'writer' => $this->stateViewWriter,
        ];
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
