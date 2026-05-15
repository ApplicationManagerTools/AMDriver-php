<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Validation;

use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;

final class JsonPayloadValidator
{
    /**
     * @param array<string, mixed> $data
     * @param list<string>         $keys
     */
    public static function requireKeys(array $data, array $keys): void
    {
        foreach ($keys as $key) {
            if (!\array_key_exists($key, $data)) {
                throw new ValidationException(sprintf('Missing required field: %s', $key));
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function requireNonEmptyString(array $data, string $key): void
    {
        if (!isset($data[$key]) || !\is_string($data[$key]) || '' === trim($data[$key])) {
            throw new ValidationException(sprintf('Field %s must be a non-empty string', $key));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseJsonObject(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!\is_array($decoded)) {
            throw new ValidationException('Payload must be a JSON object');
        }

        return $decoded;
    }

    public static function assertSchemaVersion(string $actual, string $expected): void
    {
        if ($actual !== $expected) {
            throw new ValidationException(sprintf('Unsupported schemaVersion: %s (expected %s)', $actual, $expected));
        }
    }
}
