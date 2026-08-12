<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\DeferredUpgradeInstanceDispatcherInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use RuntimeException;

final class UnconfiguredDeferredUpgradeInstanceDispatcher implements DeferredUpgradeInstanceDispatcherInterface
{
    public function dispatch(OrchestrationCommand $command): void
    {
        throw new RuntimeException('upgrade_instance_execution is "deferred" but no DeferredUpgradeInstanceDispatcherInterface implementation is registered.');
    }
}
