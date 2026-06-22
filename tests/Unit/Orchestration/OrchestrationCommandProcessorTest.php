<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\DeferredCreateInstanceDispatcherInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StartInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StopInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\CreateInstanceHandlerResult;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\HandlerFailedException;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientInterface;
use ApplicationManagerTools\AmDriver\Core\Idempotency\IdempotencyStoreInterface;
use ApplicationManagerTools\AmDriver\Core\Idempotency\OrchestrationCommandLifecycleStoreInterface;
use ApplicationManagerTools\AmDriver\Core\Orchestration\CallbackStatus;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use PHPUnit\Framework\TestCase;

final class OrchestrationCommandProcessorTest extends TestCase
{
    public function testCreateInstanceSuccessPassesLocationToCallback(): void
    {
        $command = $this->createCommand();
        $callbacks = [];
        $processor = $this->processor($callbacks);

        $processor->process($command);

        self::assertCount(1, $callbacks);
        self::assertSame('https://tenant.example/login', $callbacks[0]['location'] ?? null);
        self::assertSame('SUCCEEDED', $callbacks[0]['status'] ?? null);
    }

    public function testDeferredCreateInstanceDoesNotCallbackOnProcess(): void
    {
        $command = $this->createCommand();
        $callbacks = [];
        $dispatched = false;
        $processor = $this->processor(
            $callbacks,
            null,
            new class($dispatched) implements DeferredCreateInstanceDispatcherInterface {
                public bool $dispatched;

                public function __construct(bool &$dispatched)
                {
                    $this->dispatched = &$dispatched;
                }

                public function dispatch(OrchestrationCommand $command): void
                {
                    $this->dispatched = true;
                }
            },
            OrchestrationCommandProcessor::CREATE_INSTANCE_EXECUTION_DEFERRED,
        );

        $result = $processor->process($command);

        self::assertSame(200, $result['httpStatus']);
        self::assertFalse($result['alreadyProcessed']);
        self::assertTrue($dispatched);
        self::assertCount(0, $callbacks);
    }

    public function testDeferredExecuteCreateInstanceCallbacksWithLocation(): void
    {
        $command = $this->createCommand();
        $callbacks = [];
        $processor = $this->processor(
            $callbacks,
            null,
            null,
            OrchestrationCommandProcessor::CREATE_INSTANCE_EXECUTION_DEFERRED,
        );

        $processor->executeCreateInstance($command);

        self::assertCount(1, $callbacks);
        self::assertSame('https://tenant.example/login', $callbacks[0]['location'] ?? null);
        self::assertSame('SUCCEEDED', $callbacks[0]['status'] ?? null);
    }

    public function testDeferredReplayWhileInProgressReturnsAlreadyProcessed(): void
    {
        $command = $this->createCommand();
        $callbacks = [];
        $lifecycle = new InMemoryLifecycleStore();
        $lifecycle->markInProgress($command->idempotencyKey());
        $dispatched = false;
        $processor = $this->processor(
            $callbacks,
            $lifecycle,
            new class($dispatched) implements DeferredCreateInstanceDispatcherInterface {
                public bool $dispatched;

                public function __construct(bool &$dispatched)
                {
                    $this->dispatched = &$dispatched;
                }

                public function dispatch(OrchestrationCommand $command): void
                {
                    $this->dispatched = true;
                }
            },
            OrchestrationCommandProcessor::CREATE_INSTANCE_EXECUTION_DEFERRED,
        );

        $result = $processor->process($command);

        self::assertSame(200, $result['httpStatus']);
        self::assertTrue($result['alreadyProcessed']);
        self::assertFalse($dispatched);
        self::assertCount(0, $callbacks);
    }

    public function testDeferredValidationFailureCallbacksAndReturns400(): void
    {
        $command = OrchestrationCommand::fromArray([
            'operation' => 'CREATE_INSTANCE',
            'appId' => 'am_app_test',
            'instanceId' => 'am_ins_test',
            'idempotencyKey' => 'idem-validation',
            'occurredAt' => '2026-05-15T10:00:00.000Z',
        ]);
        $callbacks = [];
        $processor = $this->processor(
            $callbacks,
            null,
            null,
            OrchestrationCommandProcessor::CREATE_INSTANCE_EXECUTION_DEFERRED,
        );

        $result = $processor->process($command);

        self::assertSame(400, $result['httpStatus']);
        self::assertCount(1, $callbacks);
        self::assertSame('FAILED', $callbacks[0]['status'] ?? null);
    }

    public function testExecuteCreateInstanceClearsInProgressOnHandlerFailure(): void
    {
        $command = $this->createCommand();
        $callbacks = [];
        $lifecycle = new InMemoryLifecycleStore();
        $lifecycle->markInProgress($command->idempotencyKey());
        $processor = new OrchestrationCommandProcessor(
            new class implements CreateInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): CreateInstanceHandlerResult
                {
                    throw new HandlerFailedException(CallbackStatus::failed(), 'Provisioning failed');
                }
            },
            new class implements StopInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): void
                {
                }
            },
            new class implements StartInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): void
                {
                }
            },
            new class implements IdempotencyStoreInterface {
                public function has(string $idempotencyKey): bool
                {
                    return false;
                }

                public function remember(string $idempotencyKey): void
                {
                }
            },
            new class($callbacks) implements AmApiClientInterface {
                /** @var list<array<string, mixed>> */
                private $callbacks;

                /** @param list<array<string, mixed>> $callbacks */
                public function __construct(array &$callbacks)
                {
                    $this->callbacks = &$callbacks;
                }

                public function pushConsumption($event): array
                {
                    return ['statusCode' => 202, 'body' => ''];
                }

                public function reportOrchestrationCallback($request): array
                {
                    $this->callbacks[] = $request->toArray();

                    return ['statusCode' => 202, 'body' => ''];
                }
            },
            $lifecycle,
            new class implements DeferredCreateInstanceDispatcherInterface {
                public function dispatch(OrchestrationCommand $command): void
                {
                }
            },
        );

        try {
            $processor->executeCreateInstance($command);
            self::fail('Expected HandlerFailedException');
        } catch (HandlerFailedException $e) {
        }

        self::assertFalse($lifecycle->isInProgress($command->idempotencyKey()));
        self::assertSame('FAILED', $callbacks[0]['status'] ?? null);
    }

    private function createCommand(): OrchestrationCommand
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/fixtures/orchestration-command-create-enriched.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        return OrchestrationCommand::fromArray($payload);
    }

    /**
     * @param list<array<string, mixed>> $callbacks
     */
    private function processor(
        array &$callbacks,
        ?OrchestrationCommandLifecycleStoreInterface $lifecycleStore = null,
        ?DeferredCreateInstanceDispatcherInterface $deferredDispatcher = null,
        string $createInstanceExecution = OrchestrationCommandProcessor::CREATE_INSTANCE_EXECUTION_SYNC
    ): OrchestrationCommandProcessor {
        return new OrchestrationCommandProcessor(
            new class implements CreateInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): CreateInstanceHandlerResult
                {
                    return new CreateInstanceHandlerResult('https://tenant.example/login');
                }
            },
            new class implements StopInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): void
                {
                }
            },
            new class implements StartInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): void
                {
                }
            },
            new class implements IdempotencyStoreInterface {
                public function has(string $idempotencyKey): bool
                {
                    return false;
                }

                public function remember(string $idempotencyKey): void
                {
                }
            },
            new class($callbacks) implements AmApiClientInterface {
                /** @var list<array<string, mixed>> */
                private $callbacks;

                /** @param list<array<string, mixed>> $callbacks */
                public function __construct(array &$callbacks)
                {
                    $this->callbacks = &$callbacks;
                }

                public function pushConsumption($event): array
                {
                    return ['statusCode' => 202, 'body' => ''];
                }

                public function reportOrchestrationCallback($request): array
                {
                    $this->callbacks[] = $request->toArray();

                    return ['statusCode' => 202, 'body' => ''];
                }
            },
            $lifecycleStore ?? new InMemoryLifecycleStore(),
            $deferredDispatcher ?? new class implements DeferredCreateInstanceDispatcherInterface {
                public function dispatch(OrchestrationCommand $command): void
                {
                }
            },
            $createInstanceExecution,
        );
    }
}

final class InMemoryLifecycleStore implements OrchestrationCommandLifecycleStoreInterface
{
    /** @var array<string, true> */
    private $inProgress = [];

    public function isInProgress(string $idempotencyKey): bool
    {
        return isset($this->inProgress[$idempotencyKey]);
    }

    public function markInProgress(string $idempotencyKey): void
    {
        $this->inProgress[$idempotencyKey] = true;
    }

    public function clearInProgress(string $idempotencyKey): void
    {
        unset($this->inProgress[$idempotencyKey]);
    }
}
