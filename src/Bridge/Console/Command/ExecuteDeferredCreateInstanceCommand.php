<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Console\Command;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\HandlerFailedException;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Orchestration\DeferredCreateInstanceWorker;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class ExecuteDeferredCreateInstanceCommand extends Command
{
    /** @var DeferredCreateInstanceWorker */
    private $worker;

    public function __construct(DeferredCreateInstanceWorker $worker)
    {
        parent::__construct();
        $this->worker = $worker;
    }

    protected function configure(): void
    {
        $this
            ->setName('am-driver:execute-deferred-create-instance')
            ->setDescription('Execute a deferred CREATE_INSTANCE orchestration command from a JSON file')
            ->addOption('command-file', null, InputOption::VALUE_REQUIRED, 'Path to JSON orchestration command payload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $commandFile = $input->getOption('command-file');
        if (!\is_string($commandFile) || trim($commandFile) === '') {
            $io->error('Missing --command-file');

            return Command::FAILURE;
        }

        if (!is_file($commandFile)) {
            $io->error(sprintf('Command file not found: %s', $commandFile));

            return Command::FAILURE;
        }

        $raw = file_get_contents($commandFile);
        if ($raw === false) {
            $io->error(sprintf('Cannot read command file: %s', $commandFile));

            return Command::FAILURE;
        }

        try {
            $payload = JsonPayloadValidator::parseJsonObject($raw);
            $command = OrchestrationCommand::fromArray($payload);
            $this->worker->run($command);
        } catch (ValidationException|HandlerFailedException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } finally {
            if (is_file($commandFile)) {
                unlink($commandFile);
            }
        }

        return Command::SUCCESS;
    }
}
