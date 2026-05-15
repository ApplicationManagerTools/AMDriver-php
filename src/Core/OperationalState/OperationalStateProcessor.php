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

    /** @var OperationalStateStoreInterface */
    private $store;

    /** @var ResourceSnapshotManager|null */
    private $snapshotManager;

    /** @var OperationalStateReceiverInterface|null */
    private $receiver;

    public function __construct(
        OperationalStateStoreInterface $store,
        ?ResourceSnapshotManager $snapshotManager = null,
        ?OperationalStateReceiverInterface $receiver = null,
    ) {
        $this->store = $store;
        $this->snapshotManager = $snapshotManager;
        $this->receiver = $receiver;
    }

    /**
     * @param array<string, mixed> $document
     */
    public function process(array $document, ?string $expectedTenantId = null): void
    {
        InstanceOperationalStateValidator::validate($document, $expectedTenantId);

        $tenantId = (string) ($document['instance']['tenantId'] ?? '');
        if ('' === $tenantId) {
            throw new ValidationException('instance.tenantId is required');
        }

        $this->store->save($tenantId, $document);

        if (null !== $this->snapshotManager) {
            $this->snapshotManager->updateLastInboundOperationalState($tenantId, [
                'schemaVersion' => self::SCHEMA_VERSION,
                'correlationId' => (string) ($document['correlationId'] ?? ''),
                'computedAt' => (string) ($document['computedAt'] ?? ''),
                'receivedAt' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
            ]);
        }

        if (null !== $this->receiver) {
            $this->receiver->receive($document);
        }
    }
}
