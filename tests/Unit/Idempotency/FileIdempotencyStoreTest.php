<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Idempotency;

use ApplicationManagerTools\AmDriver\Core\Idempotency\FileIdempotencyStore;
use PHPUnit\Framework\TestCase;

final class FileIdempotencyStoreTest extends TestCase
{
    public function testRememberAndHas(): void
    {
        // Arrange
        $dir = sys_get_temp_dir().'/am-driver-idem-'.uniqid('', true);
        $store = new FileIdempotencyStore($dir);
        $key = 'am_ins:test:create_instance:v1';

        // Act
        self::assertFalse($store->has($key));
        $store->remember($key);

        // Assert
        self::assertTrue($store->has($key));
    }
}
