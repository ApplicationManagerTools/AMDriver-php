<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\UpgradeInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

final class NoopUpgradeInstanceHandler implements UpgradeInstanceHandlerInterface
{
    public function handle(OrchestrationCommand $command): void
    {
    }
}
