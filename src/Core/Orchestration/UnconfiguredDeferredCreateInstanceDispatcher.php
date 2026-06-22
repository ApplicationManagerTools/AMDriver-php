<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\DeferredCreateInstanceDispatcherInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use RuntimeException;

final class UnconfiguredDeferredCreateInstanceDispatcher implements DeferredCreateInstanceDispatcherInterface
{
    public function dispatch(OrchestrationCommand $command): void
    {
        throw new RuntimeException('create_instance_execution is "deferred" but no DeferredCreateInstanceDispatcherInterface implementation is registered.');
    }
}
