<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Bridge\Symfony\DependencyInjection;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\DependencyInjection\ReceiverRoutePaths;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ReceiverRoutePathsTest extends TestCase
{
    public function testDefaultPrefixBuildsReceiverPaths(): void
    {
        // Arrange
        $config = ['route_prefix' => 'am'];

        // Act
        $resolved = ReceiverRoutePaths::finalize($config);

        // Assert
        self::assertSame('/am/orchestration/commands', $resolved['orchestration_commands_path']);
        self::assertSame('/am/instance-operational-state', $resolved['operational_state_path']);
    }

    public function testMultiSegmentPrefixIsNormalized(): void
    {
        // Arrange
        $config = ['route_prefix' => '/internal/am/'];

        // Act
        $resolved = ReceiverRoutePaths::finalize($config);

        // Assert
        self::assertSame(
            '/internal/am/orchestration/commands',
            $resolved['orchestration_commands_path'],
        );
        self::assertSame(
            '/internal/am/instance-operational-state',
            $resolved['operational_state_path'],
        );
    }

    public function testExplicitPathsOverridePrefix(): void
    {
        // Arrange
        $config = [
            'route_prefix' => 'am',
            'orchestration_commands_path' => '/custom/orchestration/commands',
            'operational_state_path' => '/custom/instance-operational-state',
        ];

        // Act
        $resolved = ReceiverRoutePaths::finalize($config);

        // Assert
        self::assertSame('/custom/orchestration/commands', $resolved['orchestration_commands_path']);
        self::assertSame('/custom/instance-operational-state', $resolved['operational_state_path']);
    }

    public function testEmptyPrefixIsRejected(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        ReceiverRoutePaths::finalize(['route_prefix' => '   ']);
    }
}
