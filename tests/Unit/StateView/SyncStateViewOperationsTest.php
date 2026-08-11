<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\StateView;

use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\DeferredCreateInstanceDispatcherInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\GetInfoInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\QuotaExceededInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\SetStateViewInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StartInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StopInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\CreateInstanceHandlerResult;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientInterface;
use ApplicationManagerTools\AmDriver\Core\Idempotency\IdempotencyStoreInterface;
use ApplicationManagerTools\AmDriver\Core\Idempotency\OrchestrationCommandLifecycleStoreInterface;
use ApplicationManagerTools\AmDriver\Core\Orchestration\Operation;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use ApplicationManagerTools\AmDriver\Core\StateView\FileActualResourcesConsumptionReader;
use ApplicationManagerTools\AmDriver\Core\StateView\FileStateViewWriter;
use ApplicationManagerTools\AmDriver\Core\Tenant\FileTenantWorkspace;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class SyncStateViewOperationsTest extends TestCase
{
    public function testGetInfoDelegatesToHandler(): void
    {
        // Arrange
        $root = sys_get_temp_dir().'/am-driver-sync-'.uniqid('', true);
        $workspace = new FileTenantWorkspace($root.'/tenants');
        $instanceId = 'am_ins_1';
        $dataDir = $workspace->ensureContext($instanceId);
        mkdir($dataDir.'/am-driver', 0775, true);
        file_put_contents(
            $dataDir.'/am-driver/actual_resources_consumption.json',
            json_encode([
                'resources' => [
                    'proof_storage_mo' => [
                        'actual' => 12,
                        'measuredAt' => '2026-07-26T15:00:00+00:00',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        );
        $reader = new FileActualResourcesConsumptionReader();
        $processor = $this->processor(
            new class($workspace, $reader) implements GetInfoInstanceHandlerInterface {
                /** @var FileTenantWorkspace */
                private $workspace;

                /** @var FileActualResourcesConsumptionReader */
                private $reader;

                public function __construct(FileTenantWorkspace $workspace, FileActualResourcesConsumptionReader $reader)
                {
                    $this->workspace = $workspace;
                    $this->reader = $reader;
                }

                public function handle(OrchestrationCommand $command): array
                {
                    return $this->reader->read($this->workspace->ensureContext($command->instanceId()));
                }
            },
            new class implements SetStateViewInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): void
                {
                }
            },
        );
        $command = OrchestrationCommand::fromArray([
            'operation' => Operation::GET_INFO_INSTANCE,
            'appId' => 'am_app_1',
            'instanceId' => $instanceId,
            'occurredAt' => '2026-07-26T16:00:00+00:00',
        ]);

        // Act
        $result = $processor->process($command);

        // Assert
        self::assertSame(200, $result['httpStatus']);
        self::assertSame(12, $result['body']['resources']['proof_storage_mo']['actual']);

        $this->removeTree($root);
    }

    public function testSetStateViewDelegatesToHandler(): void
    {
        // Arrange
        $root = sys_get_temp_dir().'/am-driver-sync-'.uniqid('', true);
        $workspace = new FileTenantWorkspace($root.'/tenants');
        $writer = new FileStateViewWriter();
        $processor = $this->processor(
            new class implements GetInfoInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): array
                {
                    return [];
                }
            },
            new class($workspace, $writer) implements SetStateViewInstanceHandlerInterface {
                /** @var FileTenantWorkspace */
                private $workspace;

                /** @var FileStateViewWriter */
                private $writer;

                public function __construct(FileTenantWorkspace $workspace, FileStateViewWriter $writer)
                {
                    $this->workspace = $workspace;
                    $this->writer = $writer;
                }

                public function handle(OrchestrationCommand $command): void
                {
                    $dir = $this->workspace->ensureContext($command->instanceId());
                    $this->writer->write($dir, $command->stateView());
                }
            },
        );
        $stateView = [
            'state' => 'started',
            'instanceId' => 'am_ins_1',
            'resources' => [
                'proof_storage_mo' => ['limit' => 100, 'actual' => 12, 'remaining' => 88],
            ],
        ];
        $command = OrchestrationCommand::fromArray([
            'operation' => Operation::SET_STATEVIEW_INSTANCE,
            'appId' => 'am_app_1',
            'instanceId' => 'am_ins_1',
            'occurredAt' => '2026-07-26T16:00:00+00:00',
            'stateView' => $stateView,
        ]);

        // Act
        $result = $processor->process($command);

        // Assert
        self::assertSame(200, $result['httpStatus']);
        self::assertTrue($result['body']['updated']);
        $path = $root.'/tenants/am_ins_1/am-driver/state_view.json';
        self::assertFileExists($path);
        self::assertSame($stateView, json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR));

        $this->removeTree($root);
    }

    private function processor(
        GetInfoInstanceHandlerInterface $getInfo,
        SetStateViewInstanceHandlerInterface $setStateView
    ): OrchestrationCommandProcessor {
        $noopCallbacks = [];

        return new OrchestrationCommandProcessor(
            new class implements CreateInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): CreateInstanceHandlerResult
                {
                    return CreateInstanceHandlerResult::fromArray([
                        'startedAt' => '2026-01-01T00:00:00+00:00',
                        'integrationInstanceId' => 'x',
                    ]);
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
            new class implements QuotaExceededInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): void
                {
                }
            },
            $getInfo,
            $setStateView,
            new class implements IdempotencyStoreInterface {
                public function has(string $idempotencyKey): bool
                {
                    return false;
                }

                public function remember(string $idempotencyKey): void
                {
                }
            },
            new class($noopCallbacks) implements AmApiClientInterface {
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

                public function cancelSubscription(string $instanceId): array
                {
                    return ['statusCode' => 202, 'body' => '{"success":true}'];
                }

                public function resumeSubscription(string $instanceId): array
                {
                    return ['statusCode' => 202, 'body' => '{"success":true}'];
                }

                public function createSubscriptionUpgradeSession(string $instanceId, string $returnUrl): array
                {
                    return ['statusCode' => 200, 'body' => ''];
                }

                public function createSubscriptionResubscribeSession(string $instanceId, string $returnUrl): array
                {
                    return ['statusCode' => 200, 'body' => ''];
                }
            },
            new class implements OrchestrationCommandLifecycleStoreInterface {
                public function isInProgress(string $idempotencyKey): bool
                {
                    return false;
                }

                public function markInProgress(string $idempotencyKey): void
                {
                }

                public function clearInProgress(string $idempotencyKey): void
                {
                }
            },
            new class implements DeferredCreateInstanceDispatcherInterface {
                public function dispatch(OrchestrationCommand $command): void
                {
                }
            },
        );
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
