<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Contract;

/**
 * Sonde optionnelle : vérifie que la route récepteur des commandes d’orchestration répond.
 */
interface ConnectivityProbeInterface
{
    /**
     * @return array{status: string, message: string, checkedAt: string}
     *                                                                   status : ok|degraded|failed
     */
    public function probeOrchestrationRoute(string $orchestrationUrl, string $commandToken): array;
}
