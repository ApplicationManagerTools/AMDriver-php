<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Snapshot;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class ResourceSnapshotManager
{
    /** @var FileResourceSnapshotStore */
    private $store;

    public function __construct(FileResourceSnapshotStore $store)
    {
        $this->store = $store;
    }

    /**
     * @param string|int|float $value
     */
    public function recordMeasurement(string $tenantId, string $resourceKey, $value, ?string $measuredAt = null): void
    {
        $measuredAt = $measuredAt ?? (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
        $snapshot = $this->store->getOrCreate($tenantId)->withResourceMeasurement($resourceKey, $value, $measuredAt);
        $this->store->save($snapshot);
    }

    /**
     * @param string|int|float $value
     */
    public function markPushedToAm(string $tenantId, string $resourceKey, $value, string $occurredAt, int $httpStatus): void
    {
        $snapshot = $this->store->getOrCreate($tenantId)->withLastPushedToAm($resourceKey, $value, $occurredAt, $httpStatus);
        $this->store->save($snapshot);
    }

    public function getSnapshot(string $tenantId): ManagedInstanceResourceSnapshot
    {
        return $this->store->getOrCreate($tenantId);
    }

    /**
     * @param array<string, mixed> $operationalStateMeta
     */
    public function updateLastInboundOperationalState(string $tenantId, array $operationalStateMeta): void
    {
        $snapshot = $this->store->getOrCreate($tenantId)->withLastInboundOperationalState($operationalStateMeta);
        $this->store->save($snapshot);
    }
}
