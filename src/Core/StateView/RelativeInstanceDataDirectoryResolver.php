<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\StateView;

use ApplicationManagerTools\AmDriver\Core\Contract\InstanceDataDirectoryResolverInterface;
use ApplicationManagerTools\AmDriver\Core\Exception\InstanceDataDirectoryNotFoundException;

/**
 * Résolveur générique : `{tenantsRoot}/{queryParam|amInstanceId}`.
 * L’hôte peut fournir sa propre implémentation (ex. Captain Learning).
 */
final class RelativeInstanceDataDirectoryResolver implements InstanceDataDirectoryResolverInterface
{
    /** @var string */
    private $tenantsRoot;

    /** @var string|null */
    private $queryParamName;

    public function __construct(string $tenantsRoot, ?string $queryParamName = 'tenantId')
    {
        $this->tenantsRoot = rtrim($tenantsRoot, '/');
        $this->queryParamName = $queryParamName;
    }

    public function resolve(string $amInstanceId, array $queryParams): string
    {
        $folder = $amInstanceId;
        if (
            null !== $this->queryParamName
            && isset($queryParams[$this->queryParamName])
            && '' !== trim($queryParams[$this->queryParamName])
        ) {
            $folder = trim($queryParams[$this->queryParamName]);
        }

        $path = $this->tenantsRoot.'/'.$folder;
        if (!is_dir($path)) {
            throw new InstanceDataDirectoryNotFoundException(sprintf(
                'Instance data directory not found: %s',
                $path,
            ));
        }

        return $path;
    }
}
