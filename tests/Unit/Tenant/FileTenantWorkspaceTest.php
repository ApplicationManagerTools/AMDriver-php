<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Tenant;

use ApplicationManagerTools\AmDriver\Core\Tenant\FileTenantWorkspace;
use PHPUnit\Framework\TestCase;

final class FileTenantWorkspaceTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir().'/am-driver-tenant-ws-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        $tenantDir = $this->baseDir.'/tenant_a';
        $flag = $tenantDir.'/suspended.flag';
        if (is_file($flag)) {
            unlink($flag);
        }
        if (is_dir($tenantDir)) {
            rmdir($tenantDir);
        }
        if (is_dir($this->baseDir)) {
            rmdir($this->baseDir);
        }
    }

    public function testEnsureContextCreatesDirectoryForSanitizedTenantId(): void
    {
        // Arrange
        $workspace = new FileTenantWorkspace($this->baseDir);
        $tenantId = 'tenant/a';

        // Act
        $path = $workspace->ensureContext($tenantId);

        // Assert
        self::assertDirectoryExists($path);
        self::assertStringEndsWith('/tenant_a', $path);
    }

    public function testSuspendedFlagLifecycle(): void
    {
        // Arrange
        $workspace = new FileTenantWorkspace($this->baseDir);
        $tenantId = 'tenant_a';

        // Act
        $workspace->markSuspended($tenantId);

        // Assert
        self::assertTrue($workspace->isSuspended($tenantId));

        // Act
        $workspace->clearSuspended($tenantId);

        // Assert
        self::assertFalse($workspace->isSuspended($tenantId));
    }
}
