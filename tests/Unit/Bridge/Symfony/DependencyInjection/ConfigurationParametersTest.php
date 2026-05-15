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
            'orchestration_commands_path' => '/internal/am/orchestration/commands',
            'operational_state_path' => '/internal/am/instance-operational-state',
            'orchestration_command_token' => 'cmd-token',
            'operational_state_token' => 'state-token',
        ]], $container);

        // Assert
        self::assertTrue($container->hasParameter('am_driver.config'));
        self::assertTrue($container->hasParameter('am_driver.config.source'));
        self::assertSame('application-manager', $container->getParameter('am_driver.config.source'));
        self::assertSame(
            '/internal/am/orchestration/commands',
            $container->getParameter('am_driver.config.orchestration_commands_path'),
        );
        self::assertSame(
            '/internal/am/instance-operational-state',
            $container->getParameter('am_driver.config.operational_state_path'),
        );
        self::assertSame('cmd-token', $container->getParameter('am_driver.config.orchestration_command_token'));
    }
}
