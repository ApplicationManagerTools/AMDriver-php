<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Dto;

use ApplicationManagerTools\AmDriver\Core\Orchestration\Operation;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;

final class OrchestrationCommand
{
    /** @var Operation */
    private $operation;

    /** @var string */
    private $appId;

    /** @var string */
    private $instanceId;

    /** @var string|null */
    private $correlationId;

    /** @var string */
    private $idempotencyKey;

    /** @var string */
    private $occurredAt;

    /** @var string|null */
    private $instanceIntegrationToken;

    public function __construct(
        Operation $operation,
        string $appId,
        string $instanceId,
        string $idempotencyKey,
        string $occurredAt,
        ?string $correlationId = null,
        ?string $instanceIntegrationToken = null,
    ) {
        $this->operation = $operation;
        $this->appId = $appId;
        $this->instanceId = $instanceId;
        $this->correlationId = $correlationId;
        $this->idempotencyKey = $idempotencyKey;
        $this->occurredAt = $occurredAt;
        $this->instanceIntegrationToken = $instanceIntegrationToken;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        JsonPayloadValidator::requireKeys(
            $data,
            ['operation', 'appId', 'instanceId', 'idempotencyKey', 'occurredAt'],
        );
        foreach (['operation', 'appId', 'instanceId', 'idempotencyKey', 'occurredAt'] as $key) {
            JsonPayloadValidator::requireNonEmptyString($data, $key);
        }

        $correlationId = null;
        if (isset($data['correlationId']) && \is_string($data['correlationId'])) {
            $trimmed = trim($data['correlationId']);
            $correlationId = '' !== $trimmed ? $trimmed : null;
        }

        $instanceToken = null;
        if (isset($data['instanceIntegrationToken']) && \is_string($data['instanceIntegrationToken'])) {
            $trimmed = trim($data['instanceIntegrationToken']);
            $instanceToken = '' !== $trimmed ? $trimmed : null;
        }

        return new self(
            Operation::fromString((string) $data['operation']),
            (string) $data['appId'],
            (string) $data['instanceId'],
            (string) $data['idempotencyKey'],
            (string) $data['occurredAt'],
            $correlationId,
            $instanceToken,
        );
    }

    public function operation(): Operation
    {
        return $this->operation;
    }

    public function appId(): string
    {
        return $this->appId;
    }

    public function instanceId(): string
    {
        return $this->instanceId;
    }

    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function occurredAt(): string
    {
        return $this->occurredAt;
    }

    public function instanceIntegrationToken(): ?string
    {
        return $this->instanceIntegrationToken;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'operation' => $this->operation->toString(),
            'appId' => $this->appId,
            'instanceId' => $this->instanceId,
            'idempotencyKey' => $this->idempotencyKey,
            'occurredAt' => $this->occurredAt,
        ];
        if (null !== $this->correlationId) {
            $payload['correlationId'] = $this->correlationId;
        }
        if (null !== $this->instanceIntegrationToken) {
            $payload['instanceIntegrationToken'] = $this->instanceIntegrationToken;
        }

        return $payload;
    }
}
