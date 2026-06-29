<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

class ProcessOrchestrationCommandServiceRequest
{
    /** @var OrchestrationCommand */
    public $command;

    public function __construct(OrchestrationCommand $command)
    {
        $this->command = $command;
    }
}
