<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Bridge\Symfony;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\ResponseData;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ResponseDataTest extends TestCase
{
    public function testSuccessfulEnvelopeSerializesExpectedJsonShape(): void
    {
        // Arrange
        $data = new stdClass();
        $data->accepted = true;
        $envelope = new ResponseData(true, $data, '', '');

        // Act
        $decoded = json_decode(json_encode($envelope), true);

        // Assert
        self::assertIsArray($decoded);
        self::assertTrue($decoded['success']);
        self::assertIsArray($decoded['data']);
        self::assertTrue($decoded['data']['accepted']);
        self::assertSame('', $decoded['error']);
        self::assertSame('', $decoded['error_message']);
    }

    public function testUnsuccessfulEnvelopeSerializesNullData(): void
    {
        // Arrange
        $envelope = new ResponseData(false, null, 'ValidationException', 'Invalid payload');

        // Act
        $decoded = json_decode(json_encode($envelope), true);

        // Assert
        self::assertFalse($decoded['success']);
        self::assertNull($decoded['data']);
        self::assertSame('ValidationException', $decoded['error']);
        self::assertSame('Invalid payload', $decoded['error_message']);
    }
}
