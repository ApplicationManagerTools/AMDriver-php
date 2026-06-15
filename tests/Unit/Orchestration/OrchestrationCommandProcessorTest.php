<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StartInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StopInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\CreateInstanceHandlerResult;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientInterface;
use ApplicationManagerTools\AmDriver\Core\Idempotency\IdempotencyStoreInterface;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use PHPUnit\Framework\TestCase;

final class OrchestrationCommandProcessorTest extends TestCase
{
    public function testCreateInstanceSuccessPassesLocationToCallback(): void
    {
        // Arrange
        /** @var array<string, mixed> $payload */
        $payload = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/fixtures/orchestration-command-create.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $command = OrchestrationCommand::fromArray($payload);
        $callbacks = [];
        $processor = new OrchestrationCommandProcessor(
            new class implements CreateInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): CreateInstanceHandlerResult
                {
                    return new CreateInstanceHandlerResult('https://tenant.example/login');
                }
            },
            new class implements StopInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): void
                {
                }
            },
            new class implements StartInstanceHandlerInterface {
                public function handle(OrchestrationCommand $command): void
                {
                }
            },
            new class implements IdempotencyStoreInterface {
                public function has(string $idempotencyKey): bool
                {
                    return false;
                }

                public function remember(string $idempotencyKey): void
                {
                }
            },
            new class($callbacks) implements AmApiClientInterface {
                /** @var list<array<string, mixed>> */
                private $callbacks;

                /** @param list<array<string, mixed>> $callbacks */
                public function __construct(array &$callbacks)
                {
                    $this->callbacks = &$callbacks;
                }

                public function pushConsumption($event): array
                {
                    return ['statusCode' => 202, 'body' => ''];
                }

                public function reportOrchestrationCallback($request): array
                {
                    $this->callbacks[] = $request->toArray();

                    return ['statusCode' => 202, 'body' => ''];
                }
            },
        );

        // Act
        $processor->process($command);

        // Assert
        self::assertCount(1, $callbacks);
        self::assertSame('https://tenant.example/login', $callbacks[0]['location'] ?? null);
        self::assertSame('SUCCEEDED', $callbacks[0]['status'] ?? null);
    }
}
