<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\Factory;

use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientConfig;

final class AmApiClientConfigFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config): AmApiClientConfig
    {
        return new AmApiClientConfig(
            (string) $config['am_base_url'],
            (string) $config['application_token'],
            (float) $config['http_timeout'],
            (int) $config['consumption_max_retries'],
            (int) $config['consumption_retry_delay_ms'],
        );
    }
}
