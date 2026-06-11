<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Snapshot;

use ApplicationManagerTools\AmDriver\Core\Dto\ConsumptionWebhookEvent;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientInterface;
use ApplicationManagerTools\AmDriver\Core\Snapshot\ConsumptionPublisher;
use ApplicationManagerTools\AmDriver\Core\Snapshot\FileResourceSnapshotStore;
use ApplicationManagerTools\AmDriver\Core\Snapshot\ResourceSnapshotManager;
use PHPUnit\Framework\TestCase;

final class ConsumptionPublisherTest extends TestCase
{
    public function testMarksPushedOnlyOnHttp202(): void
    {
        // Arrange
        $dir = sys_get_temp_dir().'/am-driver-pub-'.uniqid('', true);
        $manager = new ResourceSnapshotManager(new FileResourceSnapshotStore($dir, 'captain-learning'));
        $instanceId = 'am_ins_10000000-0000-4000-8000-000000000001';
        $manager->recordMeasurement($instanceId, 'seats', 9);

        $client = new class implements AmApiClientInterface {
            /** @var int */
            public $statusCode = 200;

            public function pushConsumption(ConsumptionWebhookEvent $event): array
            {
                return ['statusCode' => $this->statusCode, 'body' => ''];
            }

            public function reportOrchestrationCallback(OrchestrationCallbackRequest $request): array
            {
                return ['statusCode' => 202, 'body' => ''];
            }
        };
        $publisher = new ConsumptionPublisher($client, $manager, 'captain-learning');

        // Act — 200 must not mark as pushed
        $client->statusCode = 200;
        $publisher->pushResourceConsumption($instanceId, 'seats');
        $after200 = $manager->getSnapshot($instanceId)->resources()[0]['lastPushedToAm'] ?? null;

        $client->statusCode = 202;
        $publisher->pushResourceConsumption($instanceId, 'seats');
        $after202 = $manager->getSnapshot($instanceId)->resources()[0]['lastPushedToAm'] ?? null;

        // Assert
        self::assertNull($after200);
        self::assertIsArray($after202);
        self::assertTrue($after202['accepted'] ?? false);
    }
}
