<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Contract;

interface ActualResourcesConsumptionReaderInterface
{
    /**
     * Lit `{dataDir}/am-driver/actual_resources_consumption.json`.
     * Fichier absent → resources vides (le caller peut exposer actual: 0).
     *
     * @return array<string, array{actual: float|int|string, measuredAt: ?string}>
     */
    public function read(string $dataDir): array;
}
