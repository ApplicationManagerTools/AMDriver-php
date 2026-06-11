<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Dto;

use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;

final class ConsumptionWebhookEvent
{
    /** @var string */
    private $instanceId;

    /** @var string */
    private $resourceKey;

    /** @var string|int|float */
    private $value;

    /** @var string */
    private $occurredAt;

    /** @var string */
    private $source;

    /**
     * @param string|int|float $value
     */
    public function __construct(
        string $instanceId,
        string $resourceKey,
        $value,
        string $occurredAt,
        string $source,
    ) {
        $this->instanceId = $instanceId;
        $this->resourceKey = $resourceKey;
        $this->value = $value;
        $this->occurredAt = $occurredAt;
        $this->source = $source;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        JsonPayloadValidator::requireKeys($data, ['instanceId', 'resourceKey', 'value', 'occurredAt', 'source']);
        JsonPayloadValidator::requireNonEmptyString($data, 'instanceId');
        JsonPayloadValidator::requireNonEmptyString($data, 'resourceKey');
        JsonPayloadValidator::requireNonEmptyString($data, 'occurredAt');
        JsonPayloadValidator::requireNonEmptyString($data, 'source');

        if (!\is_string($data['value']) && !\is_int($data['value']) && !\is_float($data['value'])) {
            throw new ValidationException('value must be a string or number');
        }

        return new self(
            (string) $data['instanceId'],
            (string) $data['resourceKey'],
            $data['value'],
            (string) $data['occurredAt'],
            (string) $data['source']
        );
    }

    public function instanceId(): string
    {
        return $this->instanceId;
    }

    public function resourceKey(): string
    {
        return $this->resourceKey;
    }

    /**
     * @return string|int|float
     */
    public function value()
    {
        return $this->value;
    }

    public function occurredAt(): string
    {
        return $this->occurredAt;
    }

    public function source(): string
    {
        return $this->source;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'instanceId' => $this->instanceId,
            'resourceKey' => $this->resourceKey,
            'value' => $this->value,
            'occurredAt' => $this->occurredAt,
            'source' => $this->source,
        ];
    }
}
