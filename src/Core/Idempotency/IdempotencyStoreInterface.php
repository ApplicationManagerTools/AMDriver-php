<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Idempotency;

interface IdempotencyStoreInterface
{
    public function has(string $idempotencyKey): bool;

    public function remember(string $idempotencyKey): void;
}
