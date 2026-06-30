<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Dto;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;
use ApplicationManagerTools\AmDriver\Core\Orchestration\CallbackStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OrchestrationCallbackRequestTest extends TestCase
{
    public function testToArrayOmitsLocationWhenNull(): void
    {
        // Arrange
        $request = new OrchestrationCallbackRequest('idem-1', CallbackStatus::succeeded());

        // Act
        $array = $request->toArray();

        // Assert
        self::assertArrayNotHasKey('location', $array);
    }

    public function testToArrayIncludesLocationWhenSet(): void
    {
        // Arrange
        $request = new OrchestrationCallbackRequest(
            'idem-1',
            CallbackStatus::succeeded(),
            null,
            'https://tenant.example/login',
        );

        // Act
        $array = $request->toArray();

        // Assert
        self::assertSame('https://tenant.example/login', $array['location']);
    }

    public function testFromArrayAcceptsOptionalLocation(): void
    {
        // Arrange
        $data = [
            'idempotencyKey' => 'idem-1',
            'status' => 'SUCCEEDED',
            'location' => 'https://tenant.example/login',
        ];

        // Act
        $request = OrchestrationCallbackRequest::fromArray($data);

        // Assert
        self::assertSame('https://tenant.example/login', $request->location());
    }

    public function testFromArrayRejectsInvalidLocationUri(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        OrchestrationCallbackRequest::fromArray([
            'idempotencyKey' => 'idem-1',
            'status' => 'SUCCEEDED',
            'location' => 'not-a-uri',
        ]);
    }

    public function testToArrayIncludesStartedAtWhenSet(): void
    {
        // Arrange
        $request = new OrchestrationCallbackRequest(
            'idem-1',
            CallbackStatus::succeeded(),
            null,
            'https://tenant.example/login',
            '2026-06-26T10:05:00+00:00',
        );

        // Act
        $array = $request->toArray();

        // Assert
        self::assertSame('2026-06-26T10:05:00+00:00', $array['startedAt']);
    }

    public function testFromArrayAcceptsStartedAt(): void
    {
        // Arrange
        $request = OrchestrationCallbackRequest::fromArray([
            'idempotencyKey' => 'idem-1',
            'status' => 'SUCCEEDED',
            'startedAt' => '2026-06-26T10:05:00+00:00',
        ]);

        // Assert
        self::assertSame('2026-06-26T10:05:00+00:00', $request->startedAt());
    }
}
