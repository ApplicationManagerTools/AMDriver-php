<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Dto;

use ApplicationManagerTools\AmDriver\Core\Orchestration\CallbackStatus;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;

final class OrchestrationCallbackRequest
{
    /** @var string */
    private $idempotencyKey;

    /** @var CallbackStatus */
    private $status;

    /** @var string|null */
    private $message;

    public function __construct(string $idempotencyKey, CallbackStatus $status, ?string $message = null)
    {
        $this->idempotencyKey = $idempotencyKey;
        $this->status = $status;
        $this->message = $message;
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

        return new self(
            (string) $data['idempotencyKey'],
            CallbackStatus::fromString((string) $data['status']),
            $message
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

        return $payload;
    }
}
