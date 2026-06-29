<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState;

use ApplicationManagerTools\AmDriver\Application\Service\Shared\Response;

class ReceiveOperationalStateServiceResponse implements Response
{
    /** @var bool */
    public $accepted;

    /** @var bool */
    public $duplicate;

    public function __construct(bool $accepted, bool $duplicate)
    {
        $this->accepted = $accepted;
        $this->duplicate = $duplicate;
    }
}
