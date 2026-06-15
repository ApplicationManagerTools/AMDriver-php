<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Contract;

use ApplicationManagerTools\AmDriver\Core\Dto\CreateInstanceHandlerResult;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

interface CreateInstanceHandlerInterface
{
    public function handle(OrchestrationCommand $command): CreateInstanceHandlerResult;
}
