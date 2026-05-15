<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Snapshot;

use ApplicationManagerTools\AmDriver\Core\Snapshot\FileResourceSnapshotStore;
use PHPUnit\Framework\TestCase;

final class FileResourceSnapshotStoreTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        $this->dataDir = sys_get_temp_dir().'/am-driver-snapshot-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        $file = $this->dataDir.'/am_ten_test.json';
        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir($this->dataDir)) {
            rmdir($this->dataDir);
        }
    }

    public function testFindByTenantIdDelegatesToLoad(): void
    {
        // Arrange
        $store = new FileResourceSnapshotStore($this->dataDir, 'application-manager');
        $tenantId = 'am_ten_test';
        $snapshot = $store->getOrCreate($tenantId);
        $store->save($snapshot->withResourceMeasurement('seats', 3, '2026-05-15T10:00:00+00:00'));

        // Act
        $found = $store->findByTenantId($tenantId);
        $missing = $store->findByTenantId('am_ten_unknown');

        // Assert
        self::assertNotNull($found);
        self::assertSame($tenantId, $found->tenantId());
        self::assertNull($missing);
    }
}
