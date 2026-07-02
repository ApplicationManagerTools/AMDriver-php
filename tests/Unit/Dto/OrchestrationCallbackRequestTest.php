<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Dto;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;
use ApplicationManagerTools\AmDriver\Core\Orchestration\CallbackStatus;
use PHPUnit\Framework\TestCase;

final class OrchestrationCallbackRequestTest extends TestCase
{
    public function testToArrayContainsOnlyIdempotencyKeyAndStatusWhenNoExtra(): void
    {
        // Arrange
        $request = new OrchestrationCallbackRequest('idem-1', CallbackStatus::succeeded());

        // Act
        $array = $request->toArray();

        // Assert
        self::assertSame(['idempotencyKey' => 'idem-1', 'status' => 'SUCCEEDED'], $array);
    }

    public function testToArrayIncludesArbitraryExtraFieldsUnknownToTheBundle(): void
    {
        // Arrange — le bundle ne connaît ni "location" ni "integrationInstanceId", il les relaie tels quels
        $request = new OrchestrationCallbackRequest(
            'idem-1',
            CallbackStatus::succeeded(),
            null,
            ['location' => 'https://tenant.example/login', 'integrationInstanceId' => 'cl_demo_67mzxiq'],
        );

        // Act
        $array = $request->toArray();

        // Assert
        self::assertSame('https://tenant.example/login', $array['location']);
        self::assertSame('cl_demo_67mzxiq', $array['integrationInstanceId']);
    }

    public function testToArrayIncludesMessageWhenSet(): void
    {
        // Arrange
        $request = new OrchestrationCallbackRequest('idem-1', CallbackStatus::failed(), 'Provisioning failed');

        // Act
        $array = $request->toArray();

        // Assert
        self::assertSame('Provisioning failed', $array['message']);
    }

    public function testToArrayInvariantFieldsCannotBeOverriddenByExtra(): void
    {
        // Arrange — un hôte maladroit fournit des clés réservées dans extra : elles ne doivent pas écraser l'enveloppe
        $request = new OrchestrationCallbackRequest(
            'idem-1',
            CallbackStatus::succeeded(),
            null,
            ['idempotencyKey' => 'hacked', 'status' => 'HACKED'],
        );

        // Act
        $array = $request->toArray();

        // Assert
        self::assertSame('idem-1', $array['idempotencyKey']);
        self::assertSame('SUCCEEDED', $array['status']);
    }

    public function testFromArrayParsesIdempotencyKeyAndStatus(): void
    {
        // Arrange
        $data = ['idempotencyKey' => 'idem-1', 'status' => 'SUCCEEDED'];

        // Act
        $request = OrchestrationCallbackRequest::fromArray($data);

        // Assert
        self::assertSame('idem-1', $request->idempotencyKey());
        self::assertSame(CallbackStatus::succeeded()->toString(), $request->status()->toString());
    }

    public function testFromArrayCollectsUnknownFieldsAsExtraWithoutValidatingThem(): void
    {
        // Arrange
        $data = [
            'idempotencyKey' => 'idem-1',
            'status' => 'SUCCEEDED',
            'location' => 'not-a-uri',
            'integrationInstanceId' => 'cl_demo_67mzxiq',
        ];

        // Act
        $request = OrchestrationCallbackRequest::fromArray($data);

        // Assert
        self::assertSame(['location' => 'not-a-uri', 'integrationInstanceId' => 'cl_demo_67mzxiq'], $request->extra());
    }
}
