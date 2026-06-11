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

    public function findByInstanceId(string $instanceId): ?ManagedInstanceResourceSnapshot
    {
        return $this->load($instanceId);
    }

    public function load(string $instanceId): ?ManagedInstanceResourceSnapshot
    {
        $path = $this->pathFor($instanceId);
        if (!is_file($path)) {
            return null;
        }

        $json = file_get_contents($path);
        if (false === $json) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return ManagedInstanceResourceSnapshot::fromArray($data);
    }

    public function save(ManagedInstanceResourceSnapshot $snapshot): void
    {
        AtomicFileWriter::write(
            $this->pathFor($snapshot->instanceId()),
            json_encode($snapshot->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }

    public function getOrCreate(string $instanceId): ManagedInstanceResourceSnapshot
    {
        $existing = $this->load($instanceId);

        return $existing ?? ManagedInstanceResourceSnapshot::empty($instanceId, $this->source);
    }

    private function pathFor(string $instanceId): string
    {
        return $this->directory.'/'.preg_replace('/[^a-zA-Z0-9._-]+/', '_', $instanceId).'.json';
    }
}
