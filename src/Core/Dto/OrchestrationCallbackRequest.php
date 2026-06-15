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

    public function __construct(
        string $idempotencyKey,
        CallbackStatus $status,
        ?string $message = null,
        ?string $location = null
    ) {
        $this->idempotencyKey = $idempotencyKey;
        $this->status = $status;
        $this->message = $message;
        $this->location = $location;
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

        return new self(
            (string) $data['idempotencyKey'],
            CallbackStatus::fromString((string) $data['status']),
            $message,
            $location,
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

        return $payload;
    }
}
