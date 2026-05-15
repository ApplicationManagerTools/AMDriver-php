<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

final class StatePushSampleCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('state:push-sample')
            ->setDescription('POST a sample instance-operational-state.v1 document to a receptacle')
            ->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'Receptacle base URL', 'http://127.0.0.1:8099')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Operational state path', '/internal/am/instance-operational-state')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'X-Instance-Operational-State-Token', 'dev-state-token')
            ->addOption('fixture', null, InputOption::VALUE_REQUIRED, 'Fixture file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fixture = $input->getOption('fixture')
            ?: dirname(__DIR__, 4).'/tests/fixtures/instance-operational-state-minimal.json';

        if (!is_file($fixture)) {
            $io->error('Fixture not found: '.$fixture);

            return Command::FAILURE;
        }

        $body = file_get_contents($fixture);
        if (false === $body) {
            $io->error('Cannot read fixture');

            return Command::FAILURE;
        }

        $url = rtrim((string) $input->getOption('base-url'), '/').(string) $input->getOption('path');
        $response = HttpClient::create()->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Instance-Operational-State-Token' => (string) $input->getOption('token'),
            ],
            'body' => $body,
        ]);

        $io->writeln(sprintf('HTTP %d', $response->getStatusCode()));
        $io->writeln($response->getContent(false));

        $status = $response->getStatusCode();

        return $status >= 200 && $status < 300 ? Command::SUCCESS : Command::FAILURE;
    }
}
