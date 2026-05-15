<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Orchestration;

use InvalidArgumentException;

final class CallbackStatus
{
    public const SUCCEEDED = 'SUCCEEDED';
    public const FAILED = 'FAILED';
    public const RETRYABLE_FAILURE = 'RETRYABLE_FAILURE';

    /** @var string */
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function succeeded(): self
    {
        return new self(self::SUCCEEDED);
    }

    public static function failed(): self
    {
        return new self(self::FAILED);
    }

    public static function retryableFailure(): self
    {
        return new self(self::RETRYABLE_FAILURE);
    }

    public static function fromString(string $value): self
    {
        if (!\in_array($value, [self::SUCCEEDED, self::FAILED, self::RETRYABLE_FAILURE], true)) {
            throw new InvalidArgumentException(sprintf('Invalid callback status: %s', $value));
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
