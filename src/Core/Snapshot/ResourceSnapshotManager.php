<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Snapshot;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class ResourceSnapshotManager
{
    /** @var ResourceSnapshotStoreInterface */
    private $store;

    public function __construct(ResourceSnapshotStoreInterface $store)
    {
        $this->store = $store;
    }

    /**
     * @param string|int|float $value
     */
    public function recordMeasurement(string $instanceId, string $resourceKey, $value, ?string $measuredAt = null): void
    {
        $measuredAt = $measuredAt ?? (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
        $snapshot = $this->store->getOrCreate($instanceId)->withResourceMeasurement($resourceKey, $value, $measuredAt);
        $this->store->save($snapshot);
    }

    /**
     * @param string|int|float $value
     */
    public function markPushedToAm(string $instanceId, string $resourceKey, $value, string $occurredAt, int $httpStatus): void
    {
        $snapshot = $this->store->getOrCreate($instanceId)->withLastPushedToAm($resourceKey, $value, $occurredAt, $httpStatus);
        $this->store->save($snapshot);
    }

    public function getSnapshot(string $instanceId): ManagedInstanceResourceSnapshot
    {
        return $this->store->getOrCreate($instanceId);
    }

    public function findByInstanceId(string $instanceId): ?ManagedInstanceResourceSnapshot
    {
        return $this->store->findByInstanceId($instanceId);
    }

    /**
     * @param array<string, mixed> $operationalStateMeta
     */
    public function updateLastInboundOperationalState(string $instanceId, array $operationalStateMeta): void
    {
        $snapshot = $this->store->getOrCreate($instanceId)->withLastInboundOperationalState($operationalStateMeta);
        $this->store->save($snapshot);
    }
}
