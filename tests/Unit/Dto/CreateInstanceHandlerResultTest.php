<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Dto;

use ApplicationManagerTools\AmDriver\Core\Dto\CreateInstanceHandlerResult;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class CreateInstanceHandlerResultTest extends TestCase
{
    public function testFromArrayRequiresStartedAt(): void
    {
        // Arrange
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CREATE_INSTANCE success requires startedAt from handler');

        // Act
        CreateInstanceHandlerResult::fromArray([
            'integrationInstanceId' => 'cl_demo_67mzxiq',
            'location' => 'https://tenant.example/login',
        ]);
    }

    public function testFromArrayRejectsEmptyStartedAt(): void
    {
        // Arrange
        $this->expectException(ValidationException::class);

        // Act
        CreateInstanceHandlerResult::fromArray([
            'startedAt' => '   ',
            'integrationInstanceId' => 'cl_demo_67mzxiq',
        ]);
    }

    public function testFromArrayRequiresIntegrationInstanceId(): void
    {
        // Arrange
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CREATE_INSTANCE success requires integrationInstanceId from handler');

        // Act
        CreateInstanceHandlerResult::fromArray([
            'startedAt' => '2026-06-26T10:05:00+00:00',
            'location' => 'https://tenant.example/login',
        ]);
    }

    public function testFromArrayRejectsEmptyIntegrationInstanceId(): void
    {
        // Arrange
        $this->expectException(ValidationException::class);

        // Act
        CreateInstanceHandlerResult::fromArray([
            'startedAt' => '2026-06-26T10:05:00+00:00',
            'integrationInstanceId' => '   ',
        ]);
    }

    public function testStartedAtIsReadable(): void
    {
        // Arrange
        $result = CreateInstanceHandlerResult::fromArray([
            'startedAt' => '2026-06-26T10:05:00+00:00',
            'integrationInstanceId' => 'cl_demo_67mzxiq',
        ]);

        // Act
        $startedAt = $result->startedAt();

        // Assert
        self::assertSame('2026-06-26T10:05:00+00:00', $startedAt);
    }

    public function testIntegrationInstanceIdIsReadable(): void
    {
        // Arrange
        $result = CreateInstanceHandlerResult::fromArray([
            'startedAt' => '2026-06-26T10:05:00+00:00',
            'integrationInstanceId' => 'cl_demo_67mzxiq',
        ]);

        // Act
        $integrationInstanceId = $result->integrationInstanceId();

        // Assert
        self::assertSame('cl_demo_67mzxiq', $integrationInstanceId);
    }

    public function testToArrayRestitutesKnownAndArbitraryFieldsUnfiltered(): void
    {
        // Arrange — le bundle ne connaît pas "location", il doit pourtant le transporter tel quel
        $result = CreateInstanceHandlerResult::fromArray([
            'startedAt' => '2026-06-26T10:05:00+00:00',
            'integrationInstanceId' => 'cl_demo_67mzxiq',
            'location' => 'https://tenant.example/login',
        ]);

        // Act
        $data = $result->toArray();

        // Assert
        self::assertSame([
            'startedAt' => '2026-06-26T10:05:00+00:00',
            'integrationInstanceId' => 'cl_demo_67mzxiq',
            'location' => 'https://tenant.example/login',
        ], $data);
    }

    public function testFromArrayRejectsNonScalarValue(): void
    {
        // Arrange
        $this->expectException(ValidationException::class);

        // Act
        CreateInstanceHandlerResult::fromArray([
            'startedAt' => '2026-06-26T10:05:00+00:00',
            'integrationInstanceId' => 'cl_demo_67mzxiq',
            'broken' => ['nested' => 'array'],
        ]);
    }
}
