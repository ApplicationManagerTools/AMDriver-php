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
    private $targetId;

    /** @var string */
    private $appId;

    /** @var string */
    private $instanceId;

    /** @var string */
    private $tenantId;

    /** @var string */
    private $correlationId;

    /** @var string */
    private $idempotencyKey;

    /** @var string */
    private $occurredAt;

    public function __construct(
        Operation $operation,
        string $targetId,
        string $appId,
        string $instanceId,
        string $tenantId,
        string $correlationId,
        string $idempotencyKey,
        string $occurredAt,
    ) {
        $this->operation = $operation;
        $this->targetId = $targetId;
        $this->appId = $appId;
        $this->instanceId = $instanceId;
        $this->tenantId = $tenantId;
        $this->correlationId = $correlationId;
        $this->idempotencyKey = $idempotencyKey;
        $this->occurredAt = $occurredAt;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        JsonPayloadValidator::requireKeys(
            $data,
            ['operation', 'targetId', 'appId', 'instanceId', 'tenantId', 'correlationId', 'idempotencyKey', 'occurredAt']
        );
        foreach (['operation', 'targetId', 'appId', 'instanceId', 'tenantId', 'correlationId', 'idempotencyKey', 'occurredAt'] as $key) {
            JsonPayloadValidator::requireNonEmptyString($data, $key);
        }

        return new self(
            Operation::fromString((string) $data['operation']),
            (string) $data['targetId'],
            (string) $data['appId'],
            (string) $data['instanceId'],
            (string) $data['tenantId'],
            (string) $data['correlationId'],
            (string) $data['idempotencyKey'],
            (string) $data['occurredAt']
        );
    }

    public function operation(): Operation
    {
        return $this->operation;
    }

    public function targetId(): string
    {
        return $this->targetId;
    }

    public function appId(): string
    {
        return $this->appId;
    }

    public function instanceId(): string
    {
        return $this->instanceId;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function correlationId(): string
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'operation' => $this->operation->toString(),
            'targetId' => $this->targetId,
            'appId' => $this->appId,
            'instanceId' => $this->instanceId,
            'tenantId' => $this->tenantId,
            'correlationId' => $this->correlationId,
            'idempotencyKey' => $this->idempotencyKey,
            'occurredAt' => $this->occurredAt,
        ];
    }
}
