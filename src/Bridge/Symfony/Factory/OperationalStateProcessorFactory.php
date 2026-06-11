<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\Factory;

use ApplicationManagerTools\AmDriver\Core\Contract\OperationalStateReceiverInterface;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateReceiptStoreInterface;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateStoreInterface;
use ApplicationManagerTools\AmDriver\Core\Snapshot\ResourceSnapshotManager;

final class OperationalStateProcessorFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(
        OperationalStateStoreInterface $store,
        OperationalStateReceiptStoreInterface $receiptStore,
        ?ResourceSnapshotManager $snapshotManager,
        ?OperationalStateReceiverInterface $receiver,
        array $config,
    ): OperationalStateProcessor {
        $expectedInstance = $config['expected_instance_id'] ?? null;

        return new OperationalStateProcessor(
            $store,
            $receiptStore,
            $snapshotManager,
            $receiver,
            \is_string($expectedInstance) && '' !== $expectedInstance ? $expectedInstance : null
        );
    }
}
