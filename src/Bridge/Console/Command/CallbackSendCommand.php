<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Console\Command;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClient;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientConfig;
use ApplicationManagerTools\AmDriver\Core\Orchestration\CallbackStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

final class CallbackSendCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('callback:send')
            ->setDescription('Send an orchestration callback to Application Manager')
            ->addOption('am-url', null, InputOption::VALUE_REQUIRED, 'AM base URL')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'X-Orchestration-Callback-Token')
            ->addOption('idempotency-key', null, InputOption::VALUE_REQUIRED, 'idempotencyKey')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'SUCCEEDED|FAILED|RETRYABLE_FAILURE', 'SUCCEEDED')
            ->addOption('message', null, InputOption::VALUE_OPTIONAL, 'Optional message');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        foreach (['am-url', 'token', 'idempotency-key'] as $required) {
            if (!$input->getOption($required)) {
                $io->error(sprintf('Missing --%s', $required));

                return Command::FAILURE;
            }
        }

        $client = new AmApiClient(HttpClient::create(), new AmApiClientConfig(
            (string) $input->getOption('am-url'),
            'unused-consumption-token',
            (string) $input->getOption('token'),
        ));

        $request = new OrchestrationCallbackRequest(
            (string) $input->getOption('idempotency-key'),
            CallbackStatus::fromString((string) $input->getOption('status')),
            $input->getOption('message') ? (string) $input->getOption('message') : null,
        );

        $response = $client->reportOrchestrationCallback($request);
        $io->writeln(sprintf('HTTP %d', $response['statusCode']));
        $io->writeln($response['body']);

        return $response['statusCode'] >= 200 && $response['statusCode'] < 300 ? Command::SUCCESS : Command::FAILURE;
    }
}
