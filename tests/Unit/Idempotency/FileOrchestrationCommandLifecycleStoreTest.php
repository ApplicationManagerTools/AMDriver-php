<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Idempotency;

use ApplicationManagerTools\AmDriver\Core\Idempotency\FileOrchestrationCommandLifecycleStore;
use PHPUnit\Framework\TestCase;

final class FileOrchestrationCommandLifecycleStoreTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/am-driver-lifecycle-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->dir)) {
            return;
        }
        $files = scandir($this->dir);
        if (\is_array($files)) {
            foreach ($files as $file) {
                if ('.' === $file || '..' === $file) {
                    continue;
                }
                unlink($this->dir.'/'.$file);
            }
        }
        rmdir($this->dir);
    }

    public function testMarkInProgressAndClear(): void
    {
        $store = new FileOrchestrationCommandLifecycleStore($this->dir);
        $key = 'idem-1';

        self::assertFalse($store->isInProgress($key));
        $store->markInProgress($key);
        self::assertTrue($store->isInProgress($key));
        $store->clearInProgress($key);
        self::assertFalse($store->isInProgress($key));
    }
}
