<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Snapshot;

use ApplicationManagerTools\AmDriver\Core\Dto\ConsumptionWebhookEvent;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class ConsumptionPublisher
{
    /** @var AmApiClientInterface */
    private $amApiClient;

    /** @var ResourceSnapshotManager */
    private $snapshotManager;

    /** @var string */
    private $source;

    public function __construct(
        AmApiClientInterface $amApiClient,
        ResourceSnapshotManager $snapshotManager,
        string $source,
    ) {
        $this->amApiClient = $amApiClient;
        $this->snapshotManager = $snapshotManager;
        $this->source = $source;
    }

    public function pushResourceConsumption(string $instanceId, string $resourceKey): int
    {
        $snapshot = $this->snapshotManager->getSnapshot($instanceId);
        $value = null;
        $occurredAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);

        foreach ($snapshot->resources() as $resource) {
            if (($resource['resourceKey'] ?? '') === $resourceKey) {
                $value = $resource['localMeasuredValue'] ?? null;
                $occurredAt = (string) ($resource['measuredAt'] ?? $occurredAt);
                break;
            }
        }

        if (null === $value) {
            throw new InvalidArgumentException(sprintf('No measurement for resourceKey %s', $resourceKey));
        }

        $event = new ConsumptionWebhookEvent($instanceId, $resourceKey, $value, $occurredAt, $this->source);
        $response = $this->amApiClient->pushConsumption($event);

        if (202 === $response['statusCode']) {
            $this->snapshotManager->markPushedToAm($instanceId, $resourceKey, $value, $occurredAt, $response['statusCode']);
        }

        return $response['statusCode'];
    }

    /**
     * @return list<int> HTTP status codes per resource pushed
     */
    public function flushPendingToAm(string $instanceId): array
    {
        $statuses = [];
        foreach ($this->snapshotManager->getSnapshot($instanceId)->resources() as $resource) {
            $resourceKey = (string) ($resource['resourceKey'] ?? '');
            if ('' === $resourceKey) {
                continue;
            }
            $lastPushed = $resource['lastPushedToAm'] ?? null;
            $localValue = $resource['localMeasuredValue'] ?? null;
            if (null === $localValue) {
                continue;
            }
            if (\is_array($lastPushed) && ($lastPushed['value'] ?? null) == $localValue && !empty($lastPushed['accepted'])) {
                continue;
            }
            $statuses[] = $this->pushResourceConsumption($instanceId, $resourceKey);
        }

        return $statuses;
    }
}
