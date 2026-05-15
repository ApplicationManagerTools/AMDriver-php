<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\OperationalState;

interface OperationalStateStoreInterface
{
    /**
     * @param array<string, mixed> $document
     */
    public function save(string $tenantId, array $document): void;

    /**
     * @return array<string, mixed>|null
     */
    public function load(string $tenantId): ?array;
}
