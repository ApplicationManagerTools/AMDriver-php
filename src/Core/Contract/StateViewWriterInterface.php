<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Contract;

interface StateViewWriterInterface
{
    /**
     * Écrit atomiquement `{dataDir}/am-driver/state_view.json`.
     *
     * @param array<string, mixed> $stateView
     */
    public function write(string $dataDir, array $stateView): void;
}
