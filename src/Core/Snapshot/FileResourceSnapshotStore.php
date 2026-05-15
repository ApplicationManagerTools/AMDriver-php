<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Snapshot;

use RuntimeException;

final class FileResourceSnapshotStore implements ResourceSnapshotStoreInterface
{
    /** @var string */
    private $directory;

    /** @var string */
    private $source;

    public function __construct(string $directory, string $source)
    {
        $this->directory = rtrim($directory, '/');
        $this->source = $source;
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Cannot create snapshot directory: %s', $this->directory));
        }
    }

    public function load(string $tenantId): ?ManagedInstanceResourceSnapshot
    {
        $path = $this->pathFor($tenantId);
        if (!is_file($path)) {
            return null;
        }
        $json = file_get_contents($path);
        if (false === $json) {
            throw new RuntimeException(sprintf('Cannot read snapshot: %s', $path));
        }
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return ManagedInstanceResourceSnapshot::fromArray($data);
    }

    public function save(ManagedInstanceResourceSnapshot $snapshot): void
    {
        AtomicFileWriter::write(
            $this->pathFor($snapshot->tenantId()),
            json_encode($snapshot->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

    public function getOrCreate(string $tenantId): ManagedInstanceResourceSnapshot
    {
        $existing = $this->load($tenantId);

        return $existing ?? ManagedInstanceResourceSnapshot::empty($tenantId, $this->source);
    }

    private function pathFor(string $tenantId): string
    {
        return $this->directory.'/'.preg_replace('/[^a-zA-Z0-9._-]+/', '_', $tenantId).'.json';
    }
}
