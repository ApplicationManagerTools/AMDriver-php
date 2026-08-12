<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Contract;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

interface DeferredUpgradeInstanceDispatcherInterface
{
    public function dispatch(OrchestrationCommand $command): void;
}
