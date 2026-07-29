<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Contract;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

interface GetInfoInstanceHandlerInterface
{
    /**
     * @return array<string, array{actual: float|int|string, measuredAt: ?string}>
     */
    public function handle(OrchestrationCommand $command): array;
}
