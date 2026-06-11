<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Tenant;

use ApplicationManagerTools\AmDriver\Core\Tenant\FileTenantWorkspace;
use PHPUnit\Framework\TestCase;

final class FileTenantWorkspaceTest extends TestCase
{
    /** @var string */
    private $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir().'/am-driver-tenant-ws-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        $instanceDir = $this->baseDir.'/instance_a';
        $flag = $instanceDir.'/suspended.flag';
        if (is_file($flag)) {
            unlink($flag);
        }
        if (is_dir($instanceDir)) {
            rmdir($instanceDir);
        }
        if (is_dir($this->baseDir)) {
            rmdir($this->baseDir);
        }
    }

    public function testEnsureContextCreatesDirectoryForSanitizedInstanceId(): void
    {
        // Arrange
        $workspace = new FileTenantWorkspace($this->baseDir);
        $instanceId = 'instance/a';

        // Act
        $path = $workspace->ensureContext($instanceId);

        // Assert
        self::assertDirectoryExists($path);
        self::assertStringEndsWith('/instance_a', $path);
    }

    public function testSuspendAndClearFlags(): void
    {
        // Arrange
        $workspace = new FileTenantWorkspace($this->baseDir);
        $instanceId = 'instance_a';

        // Act
        $workspace->markSuspended($instanceId);

        // Assert
        self::assertTrue($workspace->isSuspended($instanceId));

        // Act
        $workspace->clearSuspended($instanceId);

        // Assert
        self::assertFalse($workspace->isSuspended($instanceId));
    }
}
