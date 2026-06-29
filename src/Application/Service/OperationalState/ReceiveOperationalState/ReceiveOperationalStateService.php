<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState;

use ApplicationManagerTools\AmDriver\Application\Service\Shared\PresenterInterface;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;

class ReceiveOperationalStateService
{
    /** @var ReceiveOperationalStateServiceResponse */
    private $response;

    /** @var OperationalStateProcessor */
    private $processor;

    /** @var PresenterInterface */
    private $presenter;

    public function __construct(OperationalStateProcessor $processor, PresenterInterface $presenter)
    {
        $this->processor = $processor;
        $this->presenter = $presenter;
    }

    public function execute(ReceiveOperationalStateServiceRequest $request): void
    {
        $result = $this->processor->process($request->document);

        $this->response = new ReceiveOperationalStateServiceResponse(
            true,
            (bool) $result['duplicate'],
        );
        $this->presenter->write($this->response);
    }

    public function getResponse(): ReceiveOperationalStateServiceResponse
    {
        return $this->response;
    }
}
