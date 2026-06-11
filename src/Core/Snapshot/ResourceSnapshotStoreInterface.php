<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Snapshot;

interface ResourceSnapshotStoreInterface
{
    public function load(string $instanceId): ?ManagedInstanceResourceSnapshot;

    /**
     * Lecture externe du snapshot persisté pour une instance (alias sémantique de {@see load()}).
     */
    public function findByInstanceId(string $instanceId): ?ManagedInstanceResourceSnapshot;

    public function save(ManagedInstanceResourceSnapshot $snapshot): void;

    public function getOrCreate(string $instanceId): ManagedInstanceResourceSnapshot;
}
