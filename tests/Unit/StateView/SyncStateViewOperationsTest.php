<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\StateView;

use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\DeferredCreateInstanceDispatcherInterface;
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
use ApplicationManagerTools\AmDriver\Core\StateView\RelativeInstanceDataDirectoryResolver;
use PHPUnit\Framework\TestCase;

final class SyncStateViewOperationsTest extends TestCase
{
    public function testGetInfoReturnsResourcesFromFile(): void
    {
        // Arrange
        $root = sys_get_temp_dir().'/am-driver-sync-'.uniqid('', true);
        $tenantDir = $root.'/tenants/cl_demo';
        mkdir($tenantDir.'/am-driver', 0775, true);
        file_put_contents(
            $tenantDir.'/am-driver/actual_resources_consumption.json',
            json_encode([
                'resources' => [
                    'proof_storage_mo' => [
                        'actual' => 12,
                        'measuredAt' => '2026-07-26T15:00:00+00:00',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        );
        $processor = $this->processor($root);
        $command = OrchestrationCommand::fromArray([
            'operation' => Operation::GET_INFO_INSTANCE,
            'appId' => 'am_app_1',
            'instanceId' => 'am_ins_1',
            'occurredAt' => '2026-07-26T16:00:00+00:00',
        ]);

        // Act
        $result = $processor->process($command, ['tenantId' => 'cl_demo']);

        // Assert
        self::assertSame(200, $result['httpStatus']);
        self::assertSame(12, $result['body']['resources']['proof_storage_mo']['actual']);

        $this->removeTree($root);
    }

    public function testSetStateViewWritesFile(): void
    {
        // Arrange
        $root = sys_get_temp_dir().'/am-driver-sync-'.uniqid('', true);
        $tenantDir = $root.'/tenants/cl_demo';
        mkdir($tenantDir.'/am-driver', 0775, true);
        $processor = $this->processor($root);
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
        $result = $processor->process($command, ['tenantId' => 'cl_demo']);

        // Assert
        self::assertSame(200, $result['httpStatus']);
        self::assertTrue($result['body']['updated']);
        $written = json_decode(
            (string) file_get_contents($tenantDir.'/am-driver/state_view.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame($stateView, $written);

        $this->removeTree($root);
    }

    public function testGetInfoUnknownTenantReturns404(): void
    {
        // Arrange
        $root = sys_get_temp_dir().'/am-driver-sync-'.uniqid('', true);
        mkdir($root.'/tenants', 0775, true);
        $processor = $this->processor($root);
        $command = OrchestrationCommand::fromArray([
            'operation' => Operation::GET_INFO_INSTANCE,
            'appId' => 'am_app_1',
            'instanceId' => 'am_ins_1',
            'occurredAt' => '2026-07-26T16:00:00+00:00',
        ]);

        // Act
        $result = $processor->process($command, ['tenantId' => 'missing']);

        // Assert
        self::assertSame(404, $result['httpStatus']);
        self::assertArrayHasKey('error', $result['body']);

        $this->removeTree($root);
    }

    private function processor(string $root): OrchestrationCommandProcessor
    {
        $noopCallbacks = [];

        return new OrchestrationCommandProcessor(
            new class implements CreateInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): CreateInstanceHandlerResult
                {
                    return new CreateInstanceHandlerResult('https://x', '2026-01-01T00:00:00+00:00');
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
            OrchestrationCommandProcessor::CREATE_INSTANCE_EXECUTION_SYNC,
            new RelativeInstanceDataDirectoryResolver($root.'/tenants'),
            new FileActualResourcesConsumptionReader(),
            new FileStateViewWriter(),
        );
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
