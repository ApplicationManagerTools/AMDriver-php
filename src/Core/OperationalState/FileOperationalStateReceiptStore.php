<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\OperationalState;

use ApplicationManagerTools\AmDriver\Core\Snapshot\AtomicFileWriter;
use RuntimeException;

final class FileOperationalStateReceiptStore implements OperationalStateReceiptStoreInterface
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Cannot create receipt directory: %s', $this->directory));
        }
    }

    public function isDuplicate(string $instanceId, string $correlationId, string $computedAt): bool
    {
        if ('' === $correlationId && '' === $computedAt) {
            return false;
        }

        $stored = $this->load($instanceId);
        if (null === $stored) {
            return false;
        }

        return $stored['correlationId'] === $correlationId && $stored['computedAt'] === $computedAt;
    }

    public function remember(string $instanceId, string $correlationId, string $computedAt): void
    {
        AtomicFileWriter::write($this->pathFor($instanceId), json_encode([
            'correlationId' => $correlationId,
            'computedAt' => $computedAt,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array{correlationId: string, computedAt: string}|null
     */
    private function load(string $instanceId): ?array
    {
        $path = $this->pathFor($instanceId);
        if (!is_file($path)) {
            return null;
        }
        $json = file_get_contents($path);
        if (false === $json) {
            return null;
        }
        /** @var array{correlationId?: string, computedAt?: string} $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return [
            'correlationId' => (string) ($data['correlationId'] ?? ''),
            'computedAt' => (string) ($data['computedAt'] ?? ''),
        ];
    }

    private function pathFor(string $instanceId): string
    {
        return $this->directory.'/'.preg_replace('/[^a-zA-Z0-9._-]+/', '_', $instanceId).'-receipt.json';
    }
}
