<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Snapshot;

use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class ManagedInstanceResourceSnapshot
{
    public const SCHEMA_VERSION = 'managed-instance-resource-snapshot.v1';

    /** @var string */
    private $instanceId;

    /** @var string */
    private $source;

    /** @var string */
    private $updatedAt;

    /** @var list<array<string, mixed>> */
    private $resources;

    /** @var array<string, mixed>|null */
    private $lastInboundOperationalState;

    /**
     * @param list<array<string, mixed>> $resources
     * @param array<string, mixed>|null  $lastInboundOperationalState
     */
    public function __construct(
        string $instanceId,
        string $source,
        string $updatedAt,
        array $resources,
        ?array $lastInboundOperationalState = null,
    ) {
        $this->instanceId = $instanceId;
        $this->source = $source;
        $this->updatedAt = $updatedAt;
        $this->resources = $resources;
        $this->lastInboundOperationalState = $lastInboundOperationalState;
    }

    public static function empty(string $instanceId, string $source): self
    {
        return new self(
            $instanceId,
            $source,
            (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
            []
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        JsonPayloadValidator::requireKeys($data, ['schemaVersion', 'instanceId', 'updatedAt', 'source', 'resources']);
        JsonPayloadValidator::assertSchemaVersion((string) $data['schemaVersion'], self::SCHEMA_VERSION);
        if (!\is_array($data['resources'])) {
            throw new ValidationException('resources must be an array');
        }

        $last = null;
        if (isset($data['lastInboundOperationalState'])) {
            if (!\is_array($data['lastInboundOperationalState'])) {
                throw new ValidationException('lastInboundOperationalState must be an object');
            }
            $last = $data['lastInboundOperationalState'];
        }

        /** @var list<array<string, mixed>> $resources */
        $resources = array_values($data['resources']);

        return new self(
            (string) $data['instanceId'],
            (string) $data['source'],
            (string) $data['updatedAt'],
            $resources,
            $last
        );
    }

    public function instanceId(): string
    {
        return $this->instanceId;
    }

    public function source(): string
    {
        return $this->source;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resources(): array
    {
        return $this->resources;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function lastInboundOperationalState(): ?array
    {
        return $this->lastInboundOperationalState;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'schemaVersion' => self::SCHEMA_VERSION,
            'instanceId' => $this->instanceId,
            'updatedAt' => $this->updatedAt,
            'source' => $this->source,
            'resources' => $this->resources,
        ];
        if (null !== $this->lastInboundOperationalState) {
            $data['lastInboundOperationalState'] = $this->lastInboundOperationalState;
        }

        return $data;
    }

    public function withUpdatedAtNow(): self
    {
        return new self(
            $this->instanceId,
            $this->source,
            (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
            $this->resources,
            $this->lastInboundOperationalState
        );
    }

    /**
     * @param array<string, mixed> $lastInboundOperationalState
     */
    public function withLastInboundOperationalState(array $lastInboundOperationalState): self
    {
        return new self(
            $this->instanceId,
            $this->source,
            (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
            $this->resources,
            $lastInboundOperationalState
        );
    }

    /**
     * @param string|int|float $value
     */
    public function withResourceMeasurement(string $resourceKey, $value, string $measuredAt): self
    {
        $resources = $this->resources;
        $found = false;
        foreach ($resources as $index => $resource) {
            if (($resource['resourceKey'] ?? '') === $resourceKey) {
                $resources[$index] = array_merge($resource, [
                    'resourceKey' => $resourceKey,
                    'localMeasuredValue' => $value,
                    'measuredAt' => $measuredAt,
                ]);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $resources[] = [
                'resourceKey' => $resourceKey,
                'localMeasuredValue' => $value,
                'measuredAt' => $measuredAt,
                'lastPushedToAm' => null,
            ];
        }

        return new self(
            $this->instanceId,
            $this->source,
            (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
            $resources,
            $this->lastInboundOperationalState
        );
    }

    /**
     * @param string|int|float $value
     */
    public function withLastPushedToAm(string $resourceKey, $value, string $occurredAt, int $httpStatus): self
    {
        $resources = $this->resources;
        foreach ($resources as $index => $resource) {
            if (($resource['resourceKey'] ?? '') !== $resourceKey) {
                continue;
            }
            $resources[$index]['lastPushedToAm'] = [
                'value' => $value,
                'occurredAt' => $occurredAt,
                'httpStatus' => $httpStatus,
                'accepted' => $httpStatus >= 200 && $httpStatus < 300,
            ];
            break;
        }

        return new self(
            $this->instanceId,
            $this->source,
            (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
            $resources,
            $this->lastInboundOperationalState
        );
    }
}
