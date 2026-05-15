<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Cli;

use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\CommandCallLog;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingCreateInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingStartInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingStopInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Http\NoopAmApiClient;
use ApplicationManagerTools\AmDriver\Core\Idempotency\FileIdempotencyStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use ApplicationManagerTools\AmDriver\Core\Snapshot\FileResourceSnapshotStore;
use ApplicationManagerTools\AmDriver\Core\Snapshot\ResourceSnapshotManager;

final class ReceptacleBootstrapFactory
{
    /**
     * @param array{
     *   data_dir: string,
     *   source: string,
     *   orchestration_path: string,
     *   operational_state_path: string,
     *   token_command: string,
     *   token_state: string
     * } $config
     */
    public static function createKernel(array $config): ReceptacleHttpKernel
    {
        $dataDir = $config['data_dir'];
        $log = new CommandCallLog();

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
                new ResourceSnapshotManager(new FileResourceSnapshotStore($dataDir.'/snapshots', $config['source']))
            ),
            $config['orchestration_path'],
            $config['operational_state_path'],
            $config['token_command'],
            $config['token_state']
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function writeBootstrapFile(string $path, array $config): void
    {
        $export = var_export($config, true);
        $autoload = dirname(__DIR__, 3).'/vendor/autoload.php';
        $autoloadExport = var_export($autoload, true);
        $contents = <<<PHP
<?php

declare(strict_types=1);

require_once {$autoloadExport};

use ApplicationManagerTools\AmDriver\Core\Cli\ReceptacleBootstrapFactory;
use ApplicationManagerTools\AmDriver\Core\Cli\ReceptacleHttpKernel;

/** @var ReceptacleHttpKernel */
return ReceptacleBootstrapFactory::createKernel({$export});

PHP;
        file_put_contents($path, $contents);
    }
}
