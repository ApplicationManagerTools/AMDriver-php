<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\StateView;

use ApplicationManagerTools\AmDriver\Core\Contract\ActualResourcesConsumptionReaderInterface;
use JsonException;

final class FileActualResourcesConsumptionReader implements ActualResourcesConsumptionReaderInterface
{
    public function read(string $dataDir): array
    {
        $path = rtrim($dataDir, '/').'/am-driver/actual_resources_consumption.json';
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if (false === $raw || '' === trim($raw)) {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return [];
        }

        if (!\is_array($decoded) || !isset($decoded['resources']) || !\is_array($decoded['resources'])) {
            return [];
        }

        $resources = [];
        foreach ($decoded['resources'] as $key => $row) {
            if (!\is_string($key) || !\is_array($row) || !\array_key_exists('actual', $row)) {
                continue;
            }
            $actual = $row['actual'];
            if (!\is_float($actual) && !\is_int($actual) && !\is_string($actual)) {
                continue;
            }
            $measuredAt = null;
            if (isset($row['measuredAt']) && \is_string($row['measuredAt'])) {
                $measuredAt = $row['measuredAt'];
            }
            $resources[$key] = [
                'actual' => $actual,
                'measuredAt' => $measuredAt,
            ];
        }

        return $resources;
    }
}
