<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Orchestration;

use InvalidArgumentException;

final class Operation
{
    public const CREATE_INSTANCE = 'CREATE_INSTANCE';
    public const STOP_INSTANCE = 'STOP_INSTANCE';
    public const START_INSTANCE = 'START_INSTANCE';
    public const DESTROY_INSTANCE = 'DESTROY_INSTANCE';

    /** @var string */
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $allowed = [
            self::CREATE_INSTANCE,
            self::STOP_INSTANCE,
            self::START_INSTANCE,
            self::DESTROY_INSTANCE,
        ];
        if (!\in_array($value, $allowed, true)) {
            throw new InvalidArgumentException(sprintf('Unknown operation: %s', $value));
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function isCreate(): bool
    {
        return self::CREATE_INSTANCE === $this->value;
    }

    public function isStop(): bool
    {
        return self::STOP_INSTANCE === $this->value;
    }

    public function isStart(): bool
    {
        return self::START_INSTANCE === $this->value;
    }

    public function isDestroy(): bool
    {
        return self::DESTROY_INSTANCE === $this->value;
    }
}
