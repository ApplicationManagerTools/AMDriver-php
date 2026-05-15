<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\OperationalState;

interface OperationalStateReceiptStoreInterface
{
    public function isDuplicate(string $tenantId, string $correlationId, string $computedAt): bool;

    public function remember(string $tenantId, string $correlationId, string $computedAt): void;
}
