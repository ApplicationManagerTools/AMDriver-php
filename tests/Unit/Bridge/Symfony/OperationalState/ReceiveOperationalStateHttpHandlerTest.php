<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Bridge\Symfony\OperationalState;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState\ReceiveOperationalStateHttpHandler;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateReceiptStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use PHPUnit\Framework\TestCase;

final class ReceiveOperationalStateHttpHandlerTest extends TestCase
{
    public function testHandleReturnsUnauthorizedEnvelopeWhenTokenInvalid(): void
    {
        // Arrange
        $handler = new ReceiveOperationalStateHttpHandler($this->processor(), 'secret-token');

        // Act
        $result = $handler->handle('{}', []);

        // Assert
        self::assertSame(401, $result['status']);
        $decoded = json_decode($result['body'], true);
        self::assertFalse($decoded['success']);
        self::assertNull($decoded['data']);
    }

    public function testHandleReturnsSuccessfulEnvelope(): void
    {
        // Arrange
        $body = file_get_contents(dirname(__DIR__, 4).'/fixtures/instance-operational-state-am-minimal.json');
        self::assertNotFalse($body);
        $handler = new ReceiveOperationalStateHttpHandler($this->processor(), 'dev-application-token');
        $headers = ['X-AM-Application-Token' => ['dev-application-token']];

        // Act
        $result = $handler->handle($body, $headers);
        $decoded = json_decode($result['body'], true);

        // Assert
        self::assertSame(200, $result['status']);
        self::assertTrue($decoded['success']);
        self::assertTrue($decoded['data']['accepted']);
        self::assertFalse($decoded['data']['duplicate']);
    }

    public function testHandleReturnsValidationErrorEnvelope(): void
    {
        // Arrange
        $handler = new ReceiveOperationalStateHttpHandler($this->processor(), 'dev-application-token');
        $headers = ['X-AM-Application-Token' => ['dev-application-token']];

        // Act
        $result = $handler->handle('{}', $headers);
        $decoded = json_decode($result['body'], true);

        // Assert
        self::assertSame(400, $result['status']);
        self::assertFalse($decoded['success']);
        self::assertNotSame('', $decoded['error_message']);
    }

    private function processor(): OperationalStateProcessor
    {
        $dataDir = sys_get_temp_dir().'/am-driver-handler-'.uniqid('', true);

        return new OperationalStateProcessor(
            new FileOperationalStateStore($dataDir.'/operational-state'),
            new FileOperationalStateReceiptStore($dataDir.'/operational-state-receipts'),
        );
    }
}
