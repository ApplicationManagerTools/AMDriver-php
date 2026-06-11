<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Console\Command;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

class OrchestrationSimulateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('orchestration:simulate')
            ->setDescription('Send an orchestration command to a local receptacle or custom URL')
            ->addArgument('operation', InputArgument::OPTIONAL, 'create|stop|start', 'create')
            ->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'Receptacle base URL', 'http://127.0.0.1:8099')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Command path', '/internal/am/orchestration/commands')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'X-Orchestration-Command-Token', 'dev-command-token')
            ->addOption('instance-id', null, InputOption::VALUE_REQUIRED, 'instanceId', 'am_ins_10000000-0000-4000-8000-000000000001')
            ->addOption('app-id', null, InputOption::VALUE_REQUIRED, 'appId', 'am_app_10000000-0000-4000-8000-000000000001')
            ->addOption('target-id', null, InputOption::VALUE_REQUIRED, 'targetId', 'local-receptacle');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $map = [
            'create' => 'CREATE_INSTANCE',
            'stop' => 'STOP_INSTANCE',
            'start' => 'START_INSTANCE',
        ];
        $operationKey = strtolower((string) $input->getArgument('operation'));
        if (!isset($map[$operationKey])) {
            $io->error('operation must be create, stop or start');

            return Command::FAILURE;
        }

        $operation = $map[$operationKey];
        $instanceId = (string) $input->getOption('instance-id');
        $payload = [
            'operation' => $operation,
            'targetId' => (string) $input->getOption('target-id'),
            'appId' => (string) $input->getOption('app-id'),
            'instanceId' => $instanceId,
            'correlationId' => 'cli_'.bin2hex(random_bytes(8)),
            'idempotencyKey' => $instanceId.':'.strtolower($operationKey).'_instance:v1',
            'occurredAt' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
        ];

        $url = rtrim((string) $input->getOption('base-url'), '/').(string) $input->getOption('path');
        $client = HttpClient::create();
        $response = $client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Orchestration-Command-Token' => (string) $input->getOption('token'),
            ],
            'json' => $payload,
        ]);

        $io->writeln(sprintf('HTTP %d', $response->getStatusCode()));
        $io->writeln($response->getContent(false));

        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ? Command::SUCCESS : Command::FAILURE;
    }
}
