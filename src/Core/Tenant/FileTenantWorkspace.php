<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Tenant;

use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

/**
 * Espace disque optionnel par tenant côté application gérée (hors persistance métier hôte).
 * Utile pour handlers STOP/START locaux (ex. marqueur « suspendu ») sans coupler au produit AM.
 */
final class FileTenantWorkspace
{
    private const SUSPENDED_FLAG = 'suspended.flag';
    private const INSTANCE_TOKEN_FILE = 'instance-integration-token.txt';

    /** @var string */
    private $tenantsBaseDirectory;

    public function __construct(string $tenantsBaseDirectory)
    {
        $this->tenantsBaseDirectory = rtrim($tenantsBaseDirectory, '/');
    }

    public function ensureContext(string $tenantId): string
    {
        $dir = $this->directoryFor($tenantId);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Cannot create tenant directory: %s', $dir));
        }

        return $dir;
    }

    public function markSuspended(string $tenantId): void
    {
        $dir = $this->ensureContext($tenantId);
        $flag = $dir.'/'.self::SUSPENDED_FLAG;
        if (false === file_put_contents($flag, (new DateTimeImmutable())->format(DateTimeInterface::ATOM))) {
            throw new RuntimeException(sprintf('Cannot write %s', $flag));
        }
    }

    public function clearSuspended(string $tenantId): void
    {
        $flag = $this->directoryFor($tenantId).'/'.self::SUSPENDED_FLAG;
        if (is_file($flag) && !unlink($flag)) {
            throw new RuntimeException(sprintf('Cannot remove %s', $flag));
        }
    }

    public function isSuspended(string $tenantId): bool
    {
        return is_file($this->directoryFor($tenantId).'/'.self::SUSPENDED_FLAG);
    }

    public function storeInstanceIntegrationToken(string $tenantId, string $token): void
    {
        $dir = $this->ensureContext($tenantId);
        $path = $dir.'/'.self::INSTANCE_TOKEN_FILE;
        if (false === file_put_contents($path, $token)) {
            throw new RuntimeException(sprintf('Cannot write %s', $path));
        }
    }

    public function instanceIntegrationToken(string $tenantId): ?string
    {
        $path = $this->directoryFor($tenantId).'/'.self::INSTANCE_TOKEN_FILE;
        if (!is_file($path)) {
            return null;
        }
        $content = file_get_contents($path);

        return \is_string($content) && '' !== trim($content) ? trim($content) : null;
    }

    private function directoryFor(string $tenantId): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $tenantId) ?? $tenantId;

        return $this->tenantsBaseDirectory.'/'.$safe;
    }
}
