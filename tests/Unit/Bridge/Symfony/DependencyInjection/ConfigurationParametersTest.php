<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Bridge\Symfony\DependencyInjection;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\DependencyInjection\AmDriverExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ConfigurationParametersTest extends TestCase
{
    public function testExtensionRegistersFlattenedConfigParameters(): void
    {
        // Arrange
        $container = new ContainerBuilder();
        $extension = new AmDriverExtension();

        // Act
        $extension->load([[
            'source' => 'application-manager',
            'data_dir' => '/var/am-driver-data',
            'route_prefix' => 'internal/am',
            'application_token' => 'app-token',
        ]], $container);

        // Assert
        self::assertTrue($container->hasParameter('am_driver.config'));
        self::assertTrue($container->hasParameter('am_driver.config.source'));
        self::assertSame('application-manager', $container->getParameter('am_driver.config.source'));
        self::assertSame('internal/am', $container->getParameter('am_driver.config.route_prefix'));
        self::assertSame(
            '/internal/am/orchestration/commands',
            $container->getParameter('am_driver.config.orchestration_commands_path'),
        );
        self::assertSame(
            '/internal/am/instance-operational-state',
            $container->getParameter('am_driver.config.operational_state_path'),
        );
        self::assertSame('app-token', $container->getParameter('am_driver.config.application_token'));
    }

    public function testExtensionUsesDefaultRoutePrefixWhenOmitted(): void
    {
        // Arrange
        $container = new ContainerBuilder();
        $extension = new AmDriverExtension();

        // Act
        $extension->load([[
            'source' => 'application-manager',
            'application_token' => 'app-token',
        ]], $container);

        // Assert
        self::assertSame('am', $container->getParameter('am_driver.config.route_prefix'));
        self::assertSame(
            '/am/orchestration/commands',
            $container->getParameter('am_driver.config.orchestration_commands_path'),
        );
        self::assertSame(
            '/am/instance-operational-state',
            $container->getParameter('am_driver.config.operational_state_path'),
        );
    }
}
