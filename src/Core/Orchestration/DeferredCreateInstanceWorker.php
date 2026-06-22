<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

final class DeferredCreateInstanceWorker
{
    /** @var OrchestrationCommandProcessor */
    private $processor;

    public function __construct(OrchestrationCommandProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function run(OrchestrationCommand $command): void
    {
        $this->processor->executeCreateInstance($command);
    }
}
