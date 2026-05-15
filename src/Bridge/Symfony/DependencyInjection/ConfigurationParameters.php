<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Expose chaque clé de configuration am_driver comme paramètre Symfony
 * {@see am_driver.config.<key>} (requis par routes.yaml et services.yaml du bundle).
 */
final class ConfigurationParameters
{
    public const ROOT = 'am_driver.config';

    /**
     * @param array<string, mixed> $config
     */
    public static function register(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(self::ROOT, $config);

        foreach ($config as $key => $value) {
            $container->setParameter(self::ROOT.'.'.$key, $value);
        }
    }
}
