<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState\Presenters;

use ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState\ReceiveOperationalStateServiceResponse;
use ApplicationManagerTools\AmDriver\Application\Service\Shared\PresenterInterface;
use ApplicationManagerTools\AmDriver\Application\Service\Shared\Response;
use stdClass;

class PresenterReceiveOperationalState implements PresenterInterface
{
    /** @var ReceiveOperationalStateServiceResponse */
    private $response;

    public function write(Response $response): void
    {
        if ($response instanceof ReceiveOperationalStateServiceResponse) {
            $this->response = $response;
        }
    }

    public function read(): stdClass
    {
        $result = new stdClass();
        $result->accepted = $this->response->accepted;
        $result->duplicate = $this->response->duplicate;

        return $result;
    }
}
