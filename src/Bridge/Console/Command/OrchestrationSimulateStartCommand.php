<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Console\Command;

use Symfony\Component\Console\Input\InputInterface;

final class OrchestrationSimulateStartCommand extends OrchestrationSimulateCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('orchestration:simulate-start');
        $this->setDescription('Send START_INSTANCE to a local receptacle');
    }

    protected function execute(InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        $input->setArgument('operation', 'start');

        return parent::execute($input, $output);
    }
}
