<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Bridge\Http;

use ApplicationManagerTools\AmDriver\Bridge\Http\ResponseEncoder;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\ResponseData;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ResponseEncoderTest extends TestCase
{
    public function testEncodeReturnsJsonStringFromEnvelope(): void
    {
        // Arrange
        $data = new stdClass();
        $data->accepted = true;
        $envelope = new ResponseData(true, $data);

        // Act
        $json = ResponseEncoder::encode($envelope);
        $decoded = json_decode($json, true);

        // Assert
        self::assertIsString($json);
        self::assertTrue($decoded['success']);
        self::assertTrue($decoded['data']['accepted']);
    }

    public function testSuccessfulReturnsStatusAndEnvelopeBody(): void
    {
        // Arrange
        $data = new stdClass();
        $data->service = 'am-driver-receptacle';

        // Act
        $result = ResponseEncoder::successful($data, 200);
        $decoded = json_decode($result['body'], true);

        // Assert
        self::assertSame(200, $result['status']);
        self::assertTrue($decoded['success']);
        self::assertSame('am-driver-receptacle', $decoded['data']['service']);
    }

    public function testUnsuccessfulReturnsStatusAndErrorEnvelope(): void
    {
        // Arrange
        $exception = new ValidationException('Invalid payload');

        // Act
        $result = ResponseEncoder::unsuccessful($exception, 400);
        $decoded = json_decode($result['body'], true);

        // Assert
        self::assertSame(400, $result['status']);
        self::assertFalse($decoded['success']);
        self::assertNull($decoded['data']);
        self::assertSame(ValidationException::class, $decoded['error']);
        self::assertSame('Invalid payload', $decoded['error_message']);
    }
}
