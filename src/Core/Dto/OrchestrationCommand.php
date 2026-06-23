<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Dto;

use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Orchestration\Operation;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;

final class OrchestrationCommand
{
    private const ENRICHMENT_ONLY_CREATE_MESSAGE =
        'Enrichment fields (name, credentials, metadata) are only allowed for CREATE_INSTANCE';

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
    private $name;

    /** @var string|null */
    private $credentialsLogin;

    /** @var array<string, mixed> */
    private $metadata;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        Operation $operation,
        string $appId,
        string $instanceId,
        string $idempotencyKey,
        string $occurredAt,
        ?string $correlationId = null,
        ?string $name = null,
        ?string $credentialsLogin = null,
        array $metadata = []
    ) {
        $this->operation = $operation;
        $this->appId = $appId;
        $this->instanceId = $instanceId;
        $this->correlationId = $correlationId;
        $this->idempotencyKey = $idempotencyKey;
        $this->occurredAt = $occurredAt;
        $this->name = $name;
        $this->credentialsLogin = $credentialsLogin;
        $this->metadata = $metadata;
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

        $operation = Operation::fromString((string) $data['operation']);
        self::assertEnrichmentRules($operation, $data);

        $correlationId = self::parseOptionalNonEmptyString($data, 'correlationId');

        $name = null;
        $credentialsLogin = null;
        /** @var array<string, mixed> $metadata */
        $metadata = [];
        if ($operation->isCreate()) {
            $name = self::parseOptionalNonEmptyString($data, 'name');
            $credentialsLogin = self::parseCredentialsLogin($data);
            $metadata = self::parseMetadata($data);
        }

        return new self(
            $operation,
            (string) $data['appId'],
            (string) $data['instanceId'],
            (string) $data['idempotencyKey'],
            (string) $data['occurredAt'],
            $correlationId,
            $name,
            $credentialsLogin,
            $metadata,
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

    public function name(): ?string
    {
        return $this->name;
    }

    public function credentialsLogin(): ?string
    {
        return $this->credentialsLogin;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
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
        if ($this->operation->isCreate()) {
            if (null !== $this->name) {
                $payload['name'] = $this->name;
            }
            if (null !== $this->credentialsLogin) {
                $payload['credentials'] = ['login' => $this->credentialsLogin];
            }
            $payload['metadata'] = $this->metadata;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function assertEnrichmentRules(Operation $operation, array $data): void
    {
        if (\array_key_exists('shortname', $data)) {
            throw new ValidationException('Field shortname is no longer supported; use name for CREATE_INSTANCE');
        }

        if ($operation->isCreate()) {
            return;
        }

        foreach (['name', 'credentials', 'metadata'] as $key) {
            if (\array_key_exists($key, $data)) {
                throw new ValidationException(self::ENRICHMENT_ONLY_CREATE_MESSAGE);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function parseOptionalNonEmptyString(array $data, string $key): ?string
    {
        if (!\array_key_exists($key, $data)) {
            return null;
        }
        if (!\is_string($data[$key])) {
            throw new ValidationException(sprintf('Field %s must be a non-empty string', $key));
        }
        $trimmed = trim($data[$key]);
        if ('' === $trimmed) {
            throw new ValidationException(sprintf('Field %s must be a non-empty string', $key));
        }

        return $trimmed;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function parseCredentialsLogin(array $data): ?string
    {
        if (!\array_key_exists('credentials', $data)) {
            return null;
        }
        if (!\is_array($data['credentials'])) {
            throw new ValidationException('Field credentials must be an object');
        }
        /** @var array<string, mixed> $credentials */
        $credentials = $data['credentials'];
        if (!\array_key_exists('login', $credentials)) {
            return null;
        }
        if (!\is_string($credentials['login'])) {
            throw new ValidationException('Field credentials.login must be a non-empty string');
        }
        $trimmed = trim($credentials['login']);
        if ('' === $trimmed) {
            throw new ValidationException('Field credentials.login must be a non-empty string');
        }

        return $trimmed;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function parseMetadata(array $data): array
    {
        if (!\array_key_exists('metadata', $data)) {
            return [];
        }
        if (!\is_array($data['metadata'])) {
            throw new ValidationException('Field metadata must be an object');
        }

        return $data['metadata'];
    }
}
