<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\OperationalState;

use ApplicationManagerTools\AmDriver\Core\Snapshot\AtomicFileWriter;
use RuntimeException;

final class FileOperationalStateStore implements OperationalStateStoreInterface
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Cannot create operational state directory: %s', $this->directory));
        }
    }

    public function save(string $instanceId, array $document): void
    {
        AtomicFileWriter::write(
            $this->pathFor($instanceId),
            json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }

    public function load(string $instanceId): ?array
    {
        $path = $this->pathFor($instanceId);
        if (!is_file($path)) {
            return null;
        }

        $json = file_get_contents($path);
        if (false === $json) {
            return null;
        }

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!\is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function pathFor(string $instanceId): string
    {
        return $this->directory.'/'.preg_replace('/[^a-zA-Z0-9._-]+/', '_', $instanceId).'-operational-state.json';
    }
}
