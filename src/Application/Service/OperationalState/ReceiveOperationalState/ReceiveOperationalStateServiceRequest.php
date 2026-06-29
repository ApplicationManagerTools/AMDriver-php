<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState;

class ReceiveOperationalStateServiceRequest
{
    /** @var array<string, mixed> */
    public $document;

    /**
     * @param array<string, mixed> $document
     */
    public function __construct(array $document)
    {
        $this->document = $document;
    }
}
