<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Snapshot;

use RuntimeException;

final class AtomicFileWriter
{
    public static function write(string $targetPath, string $contents): void
    {
        $directory = \dirname($targetPath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Cannot create directory: %s', $directory));
        }

        $tmp = $targetPath.'.'.uniqid('tmp', true);
        if (false === file_put_contents($tmp, $contents)) {
            throw new RuntimeException(sprintf('Cannot write temp file: %s', $tmp));
        }

        if (!rename($tmp, $targetPath)) {
            @unlink($tmp);
            throw new RuntimeException(sprintf('Cannot rename %s to %s', $tmp, $targetPath));
        }
    }
}
