<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Console\Command;

use ApplicationManagerTools\AmDriver\Core\Dto\ConsumptionWebhookEvent;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClient;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientConfig;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

final class ConsumptionPushCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('consumption:push')
            ->setDescription('Push a consumption event to Application Manager')
            ->addOption('instance-id', null, InputOption::VALUE_REQUIRED, 'instanceId')
            ->addOption('resource-key', null, InputOption::VALUE_REQUIRED, 'resourceKey')
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'Measured value')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'source', 'am-driver-cli')
            ->addOption('occurred-at', null, InputOption::VALUE_REQUIRED, 'ISO8601 occurredAt')
            ->addOption('am-url', null, InputOption::VALUE_REQUIRED, 'AM base URL')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'X-AM-Application-Token');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        foreach (['instance-id', 'resource-key', 'value', 'am-url', 'token'] as $required) {
            if (!$input->getOption($required)) {
                $io->error(sprintf('Missing --%s', $required));

                return Command::FAILURE;
            }
        }

        $occurredAt = $input->getOption('occurred-at')
            ?: (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);

        $value = $input->getOption('value');
        if (is_numeric($value)) {
            $value = false !== strpos((string) $value, '.') ? (float) $value : (int) $value;
        }

        $client = new AmApiClient(HttpClient::create(), new AmApiClientConfig(
            (string) $input->getOption('am-url'),
            (string) $input->getOption('token'),
        ));

        $event = new ConsumptionWebhookEvent(
            (string) $input->getOption('instance-id'),
            (string) $input->getOption('resource-key'),
            $value,
            (string) $occurredAt,
            (string) $input->getOption('source'),
        );

        $response = $client->pushConsumption($event);
        $io->writeln(sprintf('HTTP %d', $response['statusCode']));
        $io->writeln($response['body']);

        return $response['statusCode'] >= 200 && $response['statusCode'] < 300 ? Command::SUCCESS : Command::FAILURE;
    }
}
