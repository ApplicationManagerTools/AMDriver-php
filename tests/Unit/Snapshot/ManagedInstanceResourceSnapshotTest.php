<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Snapshot;

use ApplicationManagerTools\AmDriver\Core\Snapshot\ManagedInstanceResourceSnapshot;
use PHPUnit\Framework\TestCase;

final class ManagedInstanceResourceSnapshotTest extends TestCase
{
    public function testRoundTripFromFixture(): void
    {
        // Arrange
        $json = file_get_contents(dirname(__DIR__, 2).'/fixtures/managed-instance-resource-snapshot-minimal.json');
        self::assertNotFalse($json);
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        // Act
        $snapshot = ManagedInstanceResourceSnapshot::fromArray($data);
        $roundTrip = $snapshot->toArray();

        // Assert
        self::assertSame(ManagedInstanceResourceSnapshot::SCHEMA_VERSION, $roundTrip['schemaVersion']);
        self::assertSame('am_ten_10000000-0000-4000-8000-000000000001', $roundTrip['tenantId']);
        self::assertCount(1, $roundTrip['resources']);
    }

    public function testRecordMeasurementAddsResource(): void
    {
        // Arrange
        $snapshot = ManagedInstanceResourceSnapshot::empty('am_ten_x', 'captain-learning');

        // Act
        $updated = $snapshot->withResourceMeasurement('seats', 12, '2026-05-14T12:00:00+00:00');

        // Assert
        self::assertSame('seats', $updated->resources()[0]['resourceKey']);
        self::assertSame(12, $updated->resources()[0]['localMeasuredValue']);
    }
}
