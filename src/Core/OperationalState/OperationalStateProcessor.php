<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\OperationalState;

use ApplicationManagerTools\AmDriver\Core\Contract\OperationalStateReceiverInterface;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Snapshot\ResourceSnapshotManager;
use ApplicationManagerTools\AmDriver\Core\Validation\InstanceOperationalStateValidator;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class OperationalStateProcessor
{
    public const SCHEMA_VERSION = 'instance-operational-state.v1';
    public const KIND = 'application_manager.instance_operational_state';

    /** @var OperationalStateStoreInterface */
    private $store;

    /** @var OperationalStateReceiptStoreInterface */
    private $receiptStore;

    /** @var ResourceSnapshotManager|null */
    private $snapshotManager;

    /** @var OperationalStateReceiverInterface|null */
    private $receiver;

    /** @var string|null */
    private $expectedTenantId;

    /** @var string|null */
    private $expectedInstanceId;

    public function __construct(
        OperationalStateStoreInterface $store,
        OperationalStateReceiptStoreInterface $receiptStore,
        ?ResourceSnapshotManager $snapshotManager = null,
        ?OperationalStateReceiverInterface $receiver = null,
        ?string $expectedTenantId = null,
        ?string $expectedInstanceId = null,
    ) {
        $this->store = $store;
        $this->receiptStore = $receiptStore;
        $this->snapshotManager = $snapshotManager;
        $this->receiver = $receiver;
        $this->expectedTenantId = $expectedTenantId;
        $this->expectedInstanceId = $expectedInstanceId;
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array{duplicate: bool}
     */
    public function process(array $document): array
    {
        InstanceOperationalStateValidator::validate(
            $document,
            $this->expectedTenantId,
            $this->expectedInstanceId
        );

        $tenantId = (string) ($document['instance']['tenantId'] ?? '');
        if ('' === $tenantId) {
            throw new ValidationException('instance.tenantId is required');
        }

        $correlationId = (string) ($document['correlationId'] ?? '');
        $computedAt = (string) ($document['computedAt'] ?? '');
        $duplicate = $this->receiptStore->isDuplicate($tenantId, $correlationId, $computedAt);

        $this->store->save($tenantId, $document);
        $this->receiptStore->remember($tenantId, $correlationId, $computedAt);

        if (null !== $this->snapshotManager) {
            $this->snapshotManager->updateLastInboundOperationalState($tenantId, [
                'schemaVersion' => self::SCHEMA_VERSION,
                'correlationId' => $correlationId,
                'computedAt' => $computedAt,
                'receivedAt' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
            ]);
        }

        if (!$duplicate && null !== $this->receiver) {
            $this->receiver->receive($document);
        }

        return ['duplicate' => $duplicate];
    }
}
