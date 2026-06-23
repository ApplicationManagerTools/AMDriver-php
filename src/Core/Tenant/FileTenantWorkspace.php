<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Tenant;

use ApplicationManagerTools\AmDriver\Core\Snapshot\AtomicFileWriter;
use RuntimeException;

/**
 * Espace disque optionnel par instance côté application gérée (hors persistance métier hôte).
 */
final class FileTenantWorkspace
{
    private const SUSPENDED_FLAG = 'suspended.flag';

    /** @var string */
    private $tenantsBaseDirectory;

    public function __construct(string $tenantsBaseDirectory)
    {
        $this->tenantsBaseDirectory = rtrim($tenantsBaseDirectory, '/');
    }

    public function ensureContext(string $instanceId): string
    {
        $dir = $this->directoryFor($instanceId);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Cannot create tenant directory: %s', $dir));
        }

        return $dir;
    }

    public function markSuspended(string $instanceId): void
    {
        $dir = $this->ensureContext($instanceId);
        AtomicFileWriter::write($dir.'/'.self::SUSPENDED_FLAG, '');
    }

    public function clearSuspended(string $instanceId): void
    {
        $flag = $this->directoryFor($instanceId).'/'.self::SUSPENDED_FLAG;
        if (is_file($flag)) {
            unlink($flag);
        }
    }

    public function isSuspended(string $instanceId): bool
    {
        return is_file($this->directoryFor($instanceId).'/'.self::SUSPENDED_FLAG);
    }

    private function directoryFor(string $instanceId): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $instanceId) ?? $instanceId;

        return $this->tenantsBaseDirectory.'/'.$safe;
    }
}
