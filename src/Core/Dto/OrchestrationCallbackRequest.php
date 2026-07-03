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

    /** @var array<string, mixed> */
    private $extra;

    /**
     * @param array<string, mixed> $extra données métier libres (ex. issues de CreateInstanceHandlerResult::toArray()),
     *                                    dont le bundle ignore la sémantique
     */
    public function __construct(
        string $idempotencyKey,
        CallbackStatus $status,
        ?string $message = null,
        array $extra = []
    ) {
        $this->idempotencyKey = $idempotencyKey;
        $this->status = $status;
        $this->message = $message;
        $this->extra = $extra;
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

        $extra = $data;
        unset($extra['idempotencyKey'], $extra['status'], $extra['message']);

        return new self(
            (string) $data['idempotencyKey'],
            CallbackStatus::fromString((string) $data['status']),
            $message,
            $extra,
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
    public function extra(): array
    {
        return $this->extra;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Les champs invariants du bundle sont posés en dernier : un hôte ne peut
        // jamais les écraser, même s'il fournit par erreur une clé de même nom dans $extra.
        $payload = $this->extra;
        $payload['idempotencyKey'] = $this->idempotencyKey;
        $payload['status'] = $this->status->toString();
        if (null !== $this->message) {
            $payload['message'] = $this->message;
        } else {
            unset($payload['message']);
        }

        return $payload;
    }
}
