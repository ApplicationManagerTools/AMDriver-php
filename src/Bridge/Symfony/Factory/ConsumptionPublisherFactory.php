<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\Factory;

use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientInterface;
use ApplicationManagerTools\AmDriver\Core\Snapshot\ConsumptionPublisher;
use ApplicationManagerTools\AmDriver\Core\Snapshot\ResourceSnapshotManager;

final class ConsumptionPublisherFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(
        AmApiClientInterface $amApiClient,
        ResourceSnapshotManager $snapshotManager,
        array $config,
    ): ConsumptionPublisher {
        return new ConsumptionPublisher($amApiClient, $snapshotManager, (string) $config['source']);
    }
}
