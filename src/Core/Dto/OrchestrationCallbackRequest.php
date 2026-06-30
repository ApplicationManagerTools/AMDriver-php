<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Dto;

use ApplicationManagerTools\AmDriver\Core\Orchestration\CallbackStatus;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;
use InvalidArgumentException;

final class OrchestrationCallbackRequest
{
    /** @var string */
    private $idempotencyKey;

    /** @var CallbackStatus */
    private $status;

    /** @var string|null */
    private $message;

    /** @var string|null */
    private $location;

    /** @var string|null */
    private $startedAt;

    public function __construct(
        string $idempotencyKey,
        CallbackStatus $status,
        ?string $message = null,
        ?string $location = null,
        ?string $startedAt = null
    ) {
        $this->idempotencyKey = $idempotencyKey;
        $this->status = $status;
        $this->message = $message;
        $this->location = $location;
        $this->startedAt = $startedAt;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        JsonPayloadValidator::requireKeys($data, ['idempotencyKey', 'status']);
        JsonPayloadValidator::requireNonEmptyString($data, 'idempotencyKey');
        JsonPayloadValidator::requireNonEmptyString($data, 'status');

        $message = isset($data['message']) && \is_string($data['message']) ? $data['message'] : null;
        $location = null;
        if (\array_key_exists('location', $data)) {
            if (null !== $data['location'] && !\is_string($data['location'])) {
                throw new InvalidArgumentException('location must be a string URI or null.');
            }
            if (\is_string($data['location']) && '' !== $data['location']) {
                if (false === filter_var($data['location'], FILTER_VALIDATE_URL)) {
                    throw new InvalidArgumentException('location must be a valid URI.');
                }
                $location = $data['location'];
            }
        }

        $startedAt = null;
        if (\array_key_exists('startedAt', $data)) {
            if (!\is_string($data['startedAt'])) {
                throw new InvalidArgumentException('startedAt must be a non-empty string.');
            }
            $trimmedStartedAt = trim($data['startedAt']);
            if ('' !== $trimmedStartedAt) {
                $startedAt = $trimmedStartedAt;
            }
        }

        return new self(
            (string) $data['idempotencyKey'],
            CallbackStatus::fromString((string) $data['status']),
            $message,
            $location,
            $startedAt,
        );
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function status(): CallbackStatus
    {
        return $this->status;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function location(): ?string
    {
        return $this->location;
    }

    public function startedAt(): ?string
    {
        return $this->startedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'idempotencyKey' => $this->idempotencyKey,
            'status' => $this->status->toString(),
        ];
        if (null !== $this->message) {
            $payload['message'] = $this->message;
        }
        if (null !== $this->location) {
            $payload['location'] = $this->location;
        }
        if (null !== $this->startedAt) {
            $payload['startedAt'] = $this->startedAt;
        }

        return $payload;
    }
}
