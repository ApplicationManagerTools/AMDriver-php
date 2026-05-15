<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Snapshot;

interface ResourceSnapshotStoreInterface
{
    public function load(string $tenantId): ?ManagedInstanceResourceSnapshot;

    /**
     * Lecture externe du snapshot persisté pour un tenant (alias sémantique de {@see load()}).
     */
    public function findByTenantId(string $tenantId): ?ManagedInstanceResourceSnapshot;

    public function save(ManagedInstanceResourceSnapshot $snapshot): void;
}
