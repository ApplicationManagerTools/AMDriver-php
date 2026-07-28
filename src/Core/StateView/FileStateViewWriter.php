<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\StateView;

use ApplicationManagerTools\AmDriver\Core\Contract\StateViewWriterInterface;
use ApplicationManagerTools\AmDriver\Core\Snapshot\AtomicFileWriter;

final class FileStateViewWriter implements StateViewWriterInterface
{
    public function write(string $dataDir, array $stateView): void
    {
        $path = rtrim($dataDir, '/').'/am-driver/state_view.json';
        AtomicFileWriter::write($path, json_encode($stateView, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }
}
