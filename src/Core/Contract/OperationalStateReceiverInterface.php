<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Contract;

interface OperationalStateReceiverInterface
{
    /**
     * @param array<string, mixed> $document instance-operational-state.v1
     */
    public function receive(array $document): void;
}
