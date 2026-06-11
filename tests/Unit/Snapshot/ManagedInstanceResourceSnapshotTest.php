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
        self::assertSame('managed-instance-resource-snapshot.v1', $roundTrip['schemaVersion']);
        self::assertSame('am_ins_10000000-0000-4000-8000-000000000001', $roundTrip['instanceId']);
        self::assertSame('captain-learning', $roundTrip['source']);
        self::assertSame([], $roundTrip['resources']);
    }
}
