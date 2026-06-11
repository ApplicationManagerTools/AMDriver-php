<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Snapshot;

use ApplicationManagerTools\AmDriver\Core\Snapshot\FileResourceSnapshotStore;
use PHPUnit\Framework\TestCase;

final class FileResourceSnapshotStoreTest extends TestCase
{
    /** @var string */
    private $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/am-driver-snapshot-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->directory.'/*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function testFindByInstanceIdDelegatesToLoad(): void
    {
        // Arrange
        $store = new FileResourceSnapshotStore($this->directory, 'test-source');
        $instanceId = 'am_ins_test';
        $snapshot = $store->getOrCreate($instanceId);
        $store->save($snapshot);

        // Act
        $found = $store->findByInstanceId($instanceId);
        $missing = $store->findByInstanceId('am_ins_unknown');

        // Assert
        self::assertNotNull($found);
        self::assertNull($missing);
        self::assertSame($instanceId, $found->instanceId());
    }
}
