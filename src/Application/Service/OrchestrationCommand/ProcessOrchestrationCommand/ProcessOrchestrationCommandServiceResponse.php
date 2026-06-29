<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand;

use ApplicationManagerTools\AmDriver\Application\Service\Shared\Response;

class ProcessOrchestrationCommandServiceResponse implements Response
{
    /** @var int */
    public $httpStatus;

    /** @var bool */
    public $alreadyProcessed;

    public function __construct(int $httpStatus, bool $alreadyProcessed)
    {
        $this->httpStatus = $httpStatus;
        $this->alreadyProcessed = $alreadyProcessed;
    }
}
