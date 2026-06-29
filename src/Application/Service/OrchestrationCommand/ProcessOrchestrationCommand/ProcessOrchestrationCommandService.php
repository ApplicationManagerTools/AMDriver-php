<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand;

use ApplicationManagerTools\AmDriver\Application\Service\Shared\PresenterInterface;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;

class ProcessOrchestrationCommandService
{
    /** @var ProcessOrchestrationCommandServiceResponse */
    private $response;

    /** @var OrchestrationCommandProcessor */
    private $processor;

    /** @var PresenterInterface */
    private $presenter;

    public function __construct(OrchestrationCommandProcessor $processor, PresenterInterface $presenter)
    {
        $this->processor = $processor;
        $this->presenter = $presenter;
    }

    public function execute(ProcessOrchestrationCommandServiceRequest $request): void
    {
        $result = $this->processor->process($request->command);

        $this->response = new ProcessOrchestrationCommandServiceResponse(
            (int) $result['httpStatus'],
            (bool) $result['alreadyProcessed'],
        );
        $this->presenter->write($this->response);
    }

    public function getResponse(): ProcessOrchestrationCommandServiceResponse
    {
        return $this->response;
    }
}
