<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Contract;

use ApplicationManagerTools\AmDriver\Core\Exception\InstanceDataDirectoryNotFoundException;

interface InstanceDataDirectoryResolverInterface
{
    /**
     * Résout le DATA_PATH tenant / instance (dossier contenant `am-driver/`).
     *
     * @param array<string, string> $queryParams paramètres de la requête HTTP (ex. tenantId)
     *
     * @throws InstanceDataDirectoryNotFoundException
     */
    public function resolve(string $amInstanceId, array $queryParams): string;
}
