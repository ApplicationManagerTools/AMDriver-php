<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\DeferredUpgradeInstanceDispatcherInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

final class NoopDeferredUpgradeInstanceDispatcher implements DeferredUpgradeInstanceDispatcherInterface
{
    public function dispatch(OrchestrationCommand $command): void
    {
    }
}
