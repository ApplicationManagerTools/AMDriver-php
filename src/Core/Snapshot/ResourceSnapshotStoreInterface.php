<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Snapshot;

interface ResourceSnapshotStoreInterface
{
    public function load(string $tenantId): ?ManagedInstanceResourceSnapshot;

    public function save(ManagedInstanceResourceSnapshot $snapshot): void;
}
