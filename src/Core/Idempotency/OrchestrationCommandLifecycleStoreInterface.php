<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Idempotency;

interface OrchestrationCommandLifecycleStoreInterface
{
    public function isInProgress(string $idempotencyKey): bool;

    public function markInProgress(string $idempotencyKey): void;

    public function clearInProgress(string $idempotencyKey): void;
}
