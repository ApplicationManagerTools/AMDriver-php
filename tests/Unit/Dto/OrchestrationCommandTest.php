<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Dto;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Orchestration\Operation;
use PHPUnit\Framework\TestCase;

final class OrchestrationCommandTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function basePayload(string $operation = Operation::CREATE_INSTANCE): array
    {
        return [
            'operation' => $operation,
            'appId' => 'am_app_10000000-0000-4000-8000-000000000001',
            'instanceId' => 'am_ins_10000000-0000-4000-8000-000000000001',
            'idempotencyKey' => 'idem-1',
            'occurredAt' => '2026-05-14T15:35:00+00:00',
        ];
    }

    public function testFromArrayCreateInstanceWithNameAndCredentialsLogin(): void
    {
        // Arrange
        $data = $this->basePayload() + [
            'name' => 'campus-26',
            'credentials' => ['login' => 'admin@example.com'],
            'metadata' => [],
        ];

        // Act
        $command = OrchestrationCommand::fromArray($data);

        // Assert
        self::assertTrue($command->operation()->isCreate());
        self::assertSame('campus-26', $command->name());
        self::assertSame('admin@example.com', $command->credentialsLogin());
        self::assertSame([], $command->metadata());
    }

    public function testFromArrayCreateInstanceWithoutEnrichment(): void
    {
        // Arrange
        $data = $this->basePayload();

        // Act
        $command = OrchestrationCommand::fromArray($data);

        // Assert
        self::assertNull($command->name());
        self::assertNull($command->credentialsLogin());
        self::assertSame([], $command->metadata());
    }

    public function testFromArrayStartInstanceWithoutEnrichment(): void
    {
        // Arrange
        $data = $this->basePayload(Operation::START_INSTANCE);

        // Act
        $command = OrchestrationCommand::fromArray($data);

        // Assert
        self::assertTrue($command->operation()->isStart());
        self::assertNull($command->name());
        self::assertNull($command->credentialsLogin());
    }

    public function testFromArrayStartInstanceRejectsName(): void
    {
        // Arrange
        $data = $this->basePayload(Operation::START_INSTANCE) + ['name' => 'campus-26'];

        // Act
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CREATE_INSTANCE');

        OrchestrationCommand::fromArray($data);
    }

    public function testFromArrayStopInstanceRejectsCredentials(): void
    {
        // Arrange
        $data = $this->basePayload(Operation::STOP_INSTANCE) + [
            'credentials' => ['login' => 'admin@example.com'],
        ];

        // Act
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CREATE_INSTANCE');

        OrchestrationCommand::fromArray($data);
    }

    public function testFromArrayCreateInstanceRejectsShortnameLegacyKey(): void
    {
        // Arrange
        $data = $this->basePayload() + ['shortname' => 'legacy'];

        // Act
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('shortname');

        OrchestrationCommand::fromArray($data);
    }

    public function testToArrayIncludesEnrichmentOnlyForCreateInstance(): void
    {
        // Arrange
        $create = OrchestrationCommand::fromArray($this->basePayload() + [
            'name' => 'demo',
            'credentials' => ['login' => 'user@example.com'],
            'metadata' => ['source' => 'billing'],
        ]);
        $start = OrchestrationCommand::fromArray($this->basePayload(Operation::START_INSTANCE));

        // Act
        $createPayload = $create->toArray();
        $startPayload = $start->toArray();

        // Assert
        self::assertSame('demo', $createPayload['name']);
        self::assertSame(['login' => 'user@example.com'], $createPayload['credentials']);
        self::assertSame(['source' => 'billing'], $createPayload['metadata']);
        self::assertArrayNotHasKey('name', $startPayload);
        self::assertArrayNotHasKey('credentials', $startPayload);
        self::assertArrayNotHasKey('metadata', $startPayload);
    }

    public function testRoundTripFromEnrichedFixture(): void
    {
        // Arrange
        $json = file_get_contents(dirname(__DIR__, 2).'/fixtures/orchestration-command-create-enriched.json');
        self::assertNotFalse($json);
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        // Act
        $command = OrchestrationCommand::fromArray($data);
        $roundTrip = $command->toArray();

        // Assert
        self::assertSame('UtilisateurSouhaite', $command->name());
        self::assertSame('client@example.com', $command->credentialsLogin());
        self::assertSame([
            'subscriptionId' => 'sub_1Q2w3e4r5t6y7u8i9o0p',
            'formulaId' => 'am_sfm_10000000-0000-4000-8000-000000000001',
            'formulaName' => 'Pro',
        ], $roundTrip['metadata']);
    }

    public function testFromArrayCreateInstanceWithBillingMetadata(): void
    {
        // Arrange
        $metadata = [
            'subscriptionId' => 'sub_billing',
            'formulaId' => 'am_sfm_20000000-0000-4000-8000-000000000002',
            'formulaName' => 'Enterprise',
        ];
        $data = $this->basePayload() + ['metadata' => $metadata];

        // Act
        $command = OrchestrationCommand::fromArray($data);

        // Assert
        self::assertSame($metadata, $command->metadata());
        self::assertSame($metadata, $command->toArray()['metadata']);
    }

    public function testFromArrayStopInstanceWithStateView(): void
    {
        // Arrange
        $stateView = [
            'state' => 'started',
            'name' => 'Campus 26',
            'instanceId' => 'am_ins_10000000-0000-4000-8000-000000000001',
            'resources' => ['Mo' => ['limit' => 100, 'actual' => 0, 'remaining' => 100]],
        ];
        $data = $this->basePayload(Operation::STOP_INSTANCE) + ['stateView' => $stateView];

        // Act
        $command = OrchestrationCommand::fromArray($data);

        // Assert
        self::assertSame($stateView, $command->stateView());
        self::assertSame($stateView, $command->toArray()['stateView']);
    }

    public function testFromArrayCreateInstanceWithStateView(): void
    {
        // Arrange
        $stateView = ['state' => 'pending', 'name' => 'Campus 26'];
        $data = $this->basePayload() + ['stateView' => $stateView, 'metadata' => []];

        // Act
        $command = OrchestrationCommand::fromArray($data);

        // Assert
        self::assertSame($stateView, $command->stateView());
    }

    public function testFromArrayRejectsInvalidStateViewType(): void
    {
        // Arrange
        $data = $this->basePayload(Operation::STOP_INSTANCE) + ['stateView' => 'invalid'];

        // Act
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('stateView');

        OrchestrationCommand::fromArray($data);
    }
}
