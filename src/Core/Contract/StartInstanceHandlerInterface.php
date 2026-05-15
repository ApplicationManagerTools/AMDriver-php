<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Contract;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

interface StartInstanceHandlerInterface
{
    public function handle(OrchestrationCommand $command): void;
}
