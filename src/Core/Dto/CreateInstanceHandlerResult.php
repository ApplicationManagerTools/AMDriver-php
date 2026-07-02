<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Dto;

use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;

/**
 * Résultat métier d'un handler CREATE_INSTANCE.
 *
 * Le bundle ne connaît et ne valide que deux champs invariants, requis par
 * Application Manager : `startedAt` et `integrationInstanceId`. Tout le reste du
 * tableau fourni par l'application hôte (ex. `location`, ...) est transporté tel
 * quel jusqu'au callback, sans que le bundle en connaisse la sémantique.
 */
final class CreateInstanceHandlerResult
{
    private const REQUIRED_NON_EMPTY_STRING_KEYS = ['startedAt', 'integrationInstanceId'];

    /** @var array<string, mixed> */
    private $data;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        foreach (self::REQUIRED_NON_EMPTY_STRING_KEYS as $requiredKey) {
            if (!isset($data[$requiredKey]) || !\is_string($data[$requiredKey]) || '' === trim($data[$requiredKey])) {
                throw new ValidationException(sprintf('CREATE_INSTANCE success requires %s from handler', $requiredKey));
            }
        }

        foreach ($data as $key => $value) {
            if ('' === $key) {
                throw new ValidationException('CreateInstanceHandlerResult keys must be non-empty strings');
            }
            if (null !== $value && !\is_scalar($value)) {
                throw new ValidationException(sprintf('CreateInstanceHandlerResult value for "%s" must be scalar or null', $key));
            }
        }

        return new self($data);
    }

    public function startedAt(): string
    {
        /** @var string $startedAt */
        $startedAt = $this->data['startedAt'];

        return $startedAt;
    }

    public function integrationInstanceId(): string
    {
        /** @var string $integrationInstanceId */
        $integrationInstanceId = $this->data['integrationInstanceId'];

        return $integrationInstanceId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
