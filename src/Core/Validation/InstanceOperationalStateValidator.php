<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Validation;

use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;

final class InstanceOperationalStateValidator
{
    /**
     * Validation minimale alignée sur le document réellement poussé par AM
     * (`BuildInstanceOperationalStateCommand`) — pas l'exemple long de la spec.
     *
     * @param array<string, mixed> $document
     */
    public static function validate(
        array $document,
        ?string $expectedInstanceId = null,
    ): void {
        JsonPayloadValidator::requireKeys($document, ['schemaVersion', 'kind', 'instance']);
        JsonPayloadValidator::assertSchemaVersion(
            (string) $document['schemaVersion'],
            OperationalStateProcessor::SCHEMA_VERSION
        );

        if (OperationalStateProcessor::KIND !== (string) $document['kind']) {
            throw new ValidationException(sprintf('Unsupported kind: %s', (string) $document['kind']));
        }

        if (!\is_array($document['instance'])) {
            throw new ValidationException('instance must be an object');
        }

        JsonPayloadValidator::requireNonEmptyString($document['instance'], 'instanceId');

        if (null !== $expectedInstanceId && $document['instance']['instanceId'] !== $expectedInstanceId) {
            throw new ValidationException('instanceId mismatch for this deployment');
        }
    }
}
