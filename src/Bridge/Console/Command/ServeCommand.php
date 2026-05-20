<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Console\Command;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\DependencyInjection\ReceiverRoutePaths;
use ApplicationManagerTools\AmDriver\Core\Cli\ReceptacleBootstrapFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ServeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('serve')
            ->setDescription('Start the managed-app receptacle HTTP server (built-in PHP server)')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Listen port', '8099')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Listen host', '127.0.0.1')
            ->addOption('token-command', null, InputOption::VALUE_REQUIRED, 'X-Orchestration-Command-Token', 'dev-command-token')
            ->addOption('token-state', null, InputOption::VALUE_REQUIRED, 'X-Instance-Operational-State-Token', 'dev-state-token')
            ->addOption('data-dir', null, InputOption::VALUE_REQUIRED, 'Persistence directory', sys_get_temp_dir().'/am-driver-receptacle')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Consumption source identifier', 'am-driver-receptacle');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dataDir = (string) $input->getOption('data-dir');
        $source = (string) $input->getOption('source');
        $orchestrationPath = ReceiverRoutePaths::orchestrationCommandsPath(ReceiverRoutePaths::DEFAULT_ROUTE_PREFIX);
        $statePath = ReceiverRoutePaths::operationalStatePath(ReceiverRoutePaths::DEFAULT_ROUTE_PREFIX);

        if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
            $io->error('Cannot create data directory');

            return Command::FAILURE;
        }

        $kernelFile = $dataDir.'/receptacle-kernel.php';
        ReceptacleBootstrapFactory::writeBootstrapFile($kernelFile, [
            'data_dir' => $dataDir,
            'source' => $source,
            'orchestration_path' => $orchestrationPath,
            'operational_state_path' => $statePath,
            'token_command' => (string) $input->getOption('token-command'),
            'token_state' => (string) $input->getOption('token-state'),
        ]);

        $router = __DIR__.'/../../../Core/Cli/ReceptacleServerRouter.php';
        $host = (string) $input->getOption('host');
        $port = (string) $input->getOption('port');
        $docRoot = sys_get_temp_dir();

        $io->success(sprintf(
            'Receptacle listening on http://%s:%s%s and http://%s:%s%s',
            $host,
            $port,
            $orchestrationPath,
            $host,
            $port,
            $statePath
        ));

        $cmd = sprintf(
            'AM_DRIVER_RECEPTACLE_KERNEL_FILE=%s php -S %s:%s -t %s %s',
            escapeshellarg($kernelFile),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($docRoot),
            escapeshellarg($router)
        );

        passthru($cmd, $exitCode);

        return (int) $exitCode;
    }
}
