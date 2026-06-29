<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Bridge\Symfony\OrchestrationCommand;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\OrchestrationCommand\OrchestrationCommandRejectedException;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OrchestrationCommand\ProcessOrchestrationCommandHttpHandler;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\CommandCallLog;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingCreateInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingStartInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Http\NoopAmApiClient;
use ApplicationManagerTools\AmDriver\Core\Idempotency\FileIdempotencyStore;
use ApplicationManagerTools\AmDriver\Core\Idempotency\FileOrchestrationCommandLifecycleStore;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use ApplicationManagerTools\AmDriver\Core\Orchestration\UnconfiguredDeferredCreateInstanceDispatcher;
use ApplicationManagerTools\AmDriver\Tests\TestSupport\RetryableFailingStopInstanceHandler;
use PHPUnit\Framework\TestCase;

final class ProcessOrchestrationCommandHttpHandlerTest extends TestCase
{
    public function testHandleReturnsUnauthorizedEnvelopeWhenTokenInvalid(): void
    {
        // Arrange
        $handler = new ProcessOrchestrationCommandHttpHandler($this->processor(), 'secret-token');

        // Act
        $result = $handler->handle('{}', []);

        // Assert
        self::assertSame(401, $result['status']);
        $decoded = json_decode($result['body'], true);
        self::assertFalse($decoded['success']);
    }

    public function testHandleReturnsSuccessfulEnvelope(): void
    {
        // Arrange
        $body = file_get_contents(dirname(__DIR__, 4).'/fixtures/orchestration-command-create.json');
        self::assertNotFalse($body);
        $handler = new ProcessOrchestrationCommandHttpHandler($this->processor(), 'dev-application-token');
        $headers = ['X-AM-Application-Token' => ['dev-application-token']];

        // Act
        $result = $handler->handle($body, $headers);
        $decoded = json_decode($result['body'], true);

        // Assert
        self::assertSame(200, $result['status']);
        self::assertTrue($decoded['success']);
        self::assertTrue($decoded['data']['accepted']);
        self::assertFalse($decoded['data']['alreadyProcessed']);
    }

    public function testHandleReturnsProcessorFailureEnvelope(): void
    {
        // Arrange
        $body = file_get_contents(dirname(__DIR__, 4).'/fixtures/orchestration-command-create.json');
        self::assertNotFalse($body);
        $payload = json_decode($body, true);
        self::assertIsArray($payload);
        $payload['operation'] = 'STOP_INSTANCE';
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $handler = new ProcessOrchestrationCommandHttpHandler($this->processor(true), 'dev-application-token');
        $headers = ['X-AM-Application-Token' => ['dev-application-token']];

        // Act
        $result = $handler->handle($body, $headers);
        $decoded = json_decode($result['body'], true);

        // Assert
        self::assertSame(500, $result['status']);
        self::assertFalse($decoded['success']);
        self::assertSame(OrchestrationCommandRejectedException::class, $decoded['error']);
    }

    public function testHandleReturnsValidationErrorEnvelope(): void
    {
        // Arrange
        $handler = new ProcessOrchestrationCommandHttpHandler($this->processor(), 'dev-application-token');
        $headers = ['X-AM-Application-Token' => ['dev-application-token']];

        // Act
        $result = $handler->handle('{}', $headers);
        $decoded = json_decode($result['body'], true);

        // Assert
        self::assertSame(400, $result['status']);
        self::assertFalse($decoded['success']);
        self::assertSame(ValidationException::class, $decoded['error']);
    }

    private function processor(bool $failingStop = false): OrchestrationCommandProcessor
    {
        $dataDir = sys_get_temp_dir().'/am-driver-handler-'.uniqid('', true);
        $log = new CommandCallLog();

        return new OrchestrationCommandProcessor(
            new LoggingCreateInstanceHandler($log),
            $failingStop ? new RetryableFailingStopInstanceHandler() : new \ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingStopInstanceHandler($log),
            new LoggingStartInstanceHandler($log),
            new FileIdempotencyStore($dataDir.'/idempotency'),
            new NoopAmApiClient(),
            new FileOrchestrationCommandLifecycleStore($dataDir.'/lifecycle'),
            new UnconfiguredDeferredCreateInstanceDispatcher(),
        );
    }
}
