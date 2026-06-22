<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\Factory;

use ApplicationManagerTools\AmDriver\Core\Idempotency\FileIdempotencyStore;
use ApplicationManagerTools\AmDriver\Core\Idempotency\FileOrchestrationCommandLifecycleStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateReceiptStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateStore;
use ApplicationManagerTools\AmDriver\Core\Snapshot\FileResourceSnapshotStore;

final class StorageFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function idempotencyStore(array $config): FileIdempotencyStore
    {
        return new FileIdempotencyStore(self::dataDir($config).'/idempotency');
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function lifecycleStore(array $config): FileOrchestrationCommandLifecycleStore
    {
        return new FileOrchestrationCommandLifecycleStore(self::dataDir($config).'/idempotency-in-progress');
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function resourceSnapshotStore(array $config): FileResourceSnapshotStore
    {
        return new FileResourceSnapshotStore(self::dataDir($config).'/snapshots', (string) $config['source']);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function operationalStateStore(array $config): FileOperationalStateStore
    {
        return new FileOperationalStateStore(self::dataDir($config).'/operational-state');
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function operationalStateReceiptStore(array $config): FileOperationalStateReceiptStore
    {
        return new FileOperationalStateReceiptStore(self::dataDir($config).'/operational-state-receipts');
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function dataDir(array $config): string
    {
        return (string) $config['data_dir'];
    }
}
