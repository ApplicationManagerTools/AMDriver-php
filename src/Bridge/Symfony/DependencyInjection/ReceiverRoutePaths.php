<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\DependencyInjection;

use InvalidArgumentException;

/**
 * Builds receptacle HTTP paths from {@see route_prefix} with optional full-path overrides.
 */
final class ReceiverRoutePaths
{
    public const DEFAULT_ROUTE_PREFIX = 'am';

    public const ORCHESTRATION_COMMANDS_SUFFIX = '/orchestration/commands';

    public const OPERATIONAL_STATE_SUFFIX = '/instance-operational-state';

    public static function normalizePrefix(string $prefix): string
    {
        $trimmed = trim($prefix, " \t\n\r\0\x0B/");

        if ('' === $trimmed) {
            throw new InvalidArgumentException('am_driver.route_prefix must not be empty.');
        }

        return '/'.$trimmed;
    }

    public static function orchestrationCommandsPath(string $prefix): string
    {
        return self::normalizePrefix($prefix).self::ORCHESTRATION_COMMANDS_SUFFIX;
    }

    public static function operationalStatePath(string $prefix): string
    {
        return self::normalizePrefix($prefix).self::OPERATIONAL_STATE_SUFFIX;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public static function finalize(array $config): array
    {
        $prefix = isset($config['route_prefix'])
            ? (string) $config['route_prefix']
            : self::DEFAULT_ROUTE_PREFIX;

        $config['route_prefix'] = $prefix;
        $config['orchestration_commands_path'] ??= self::orchestrationCommandsPath($prefix);
        $config['operational_state_path'] ??= self::operationalStatePath($prefix);

        return $config;
    }
}
