<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Idempotency;

use ApplicationManagerTools\AmDriver\Core\Snapshot\AtomicFileWriter;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;

final class FileOrchestrationCommandLifecycleStore implements OrchestrationCommandLifecycleStoreInterface
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Cannot create lifecycle directory: %s', $this->directory));
        }
    }

    public function isInProgress(string $idempotencyKey): bool
    {
        return is_file($this->pathFor($idempotencyKey));
    }

    public function markInProgress(string $idempotencyKey): void
    {
        AtomicFileWriter::write($this->pathFor($idempotencyKey), json_encode([
            'idempotencyKey' => $idempotencyKey,
            'markedAt' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR));
    }

    public function clearInProgress(string $idempotencyKey): void
    {
        $path = $this->pathFor($idempotencyKey);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function pathFor(string $idempotencyKey): string
    {
        return $this->directory.'/'.hash('sha256', $idempotencyKey).'.json';
    }
}
