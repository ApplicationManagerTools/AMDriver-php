<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Integration;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\DependencyInjection\ReceiverRoutePaths;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\CommandCallLog;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingCreateInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingStartInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingStopInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Cli\ReceptacleHttpKernel;
use ApplicationManagerTools\AmDriver\Core\Http\NoopAmApiClient;
use ApplicationManagerTools\AmDriver\Core\Idempotency\FileIdempotencyStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateReceiptStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use ApplicationManagerTools\AmDriver\Core\Snapshot\FileResourceSnapshotStore;
use ApplicationManagerTools\AmDriver\Core\Snapshot\ResourceSnapshotManager;
use PHPUnit\Framework\TestCase;

final class ReceptacleHttpKernelTest extends TestCase
{
    public function testOrchestrationCreateAndIdempotency(): void
    {
        // Arrange
        $dataDir = sys_get_temp_dir().'/am-driver-it-'.uniqid('', true);
        $log = new CommandCallLog();
        $kernel = $this->kernel($dataDir, $log);
        $body = file_get_contents(dirname(__DIR__).'/fixtures/orchestration-command-create.json');
        self::assertNotFalse($body);
        $headers = ['X-Orchestration-Command-Token' => ['dev-command-token']];

        // Act
        $orchestrationPath = ReceiverRoutePaths::orchestrationCommandsPath(ReceiverRoutePaths::DEFAULT_ROUTE_PREFIX);
        [$status1] = $kernel->handle('POST', $orchestrationPath, $body, $headers);
        [$status2] = $kernel->handle('POST', $orchestrationPath, $body, $headers);

        // Assert
        self::assertSame(200, $status1);
        self::assertSame(200, $status2);
        self::assertCount(1, $log->entries());
    }

    public function testOperationalStatePersistsSnapshotCorrelation(): void
    {
        // Arrange
        $dataDir = sys_get_temp_dir().'/am-driver-it-'.uniqid('', true);
        $kernel = $this->kernel($dataDir, new CommandCallLog());
        $body = file_get_contents(dirname(__DIR__).'/fixtures/instance-operational-state-am-minimal.json');
        self::assertNotFalse($body);
        $headers = ['X-Instance-Operational-State-Token' => ['dev-state-token']];

        // Act
        $statePath = ReceiverRoutePaths::operationalStatePath(ReceiverRoutePaths::DEFAULT_ROUTE_PREFIX);
        [$status] = $kernel->handle('POST', $statePath, $body, $headers);
        $snapshotStore = new FileResourceSnapshotStore($dataDir.'/snapshots', 'captain-learning');
        $snapshot = $snapshotStore->load('am_ins_30000000-0000-4000-8000-000000000001');

        // Assert
        self::assertSame(200, $status);
        self::assertNotNull($snapshot);
        self::assertNotNull($snapshot->lastInboundOperationalState());
        self::assertSame('instance-operational-state.v1', $snapshot->lastInboundOperationalState()['schemaVersion']);
    }

    private function kernel(string $dataDir, CommandCallLog $log): ReceptacleHttpKernel
    {
        return new ReceptacleHttpKernel(
            new OrchestrationCommandProcessor(
                new LoggingCreateInstanceHandler($log),
                new LoggingStopInstanceHandler($log),
                new LoggingStartInstanceHandler($log),
                new FileIdempotencyStore($dataDir.'/idempotency'),
                new NoopAmApiClient()
            ),
            new OperationalStateProcessor(
                new FileOperationalStateStore($dataDir.'/operational-state'),
                new FileOperationalStateReceiptStore($dataDir.'/operational-state-receipts'),
                new ResourceSnapshotManager(new FileResourceSnapshotStore($dataDir.'/snapshots', 'captain-learning'))
            ),
            ReceiverRoutePaths::orchestrationCommandsPath(ReceiverRoutePaths::DEFAULT_ROUTE_PREFIX),
            ReceiverRoutePaths::operationalStatePath(ReceiverRoutePaths::DEFAULT_ROUTE_PREFIX),
            'dev-command-token',
            'dev-state-token'
        );
    }
}
