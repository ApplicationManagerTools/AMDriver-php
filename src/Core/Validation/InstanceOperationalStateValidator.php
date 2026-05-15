<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Validation;

use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;

final class InstanceOperationalStateValidator
{
    /**
     * @param array<string, mixed> $document
     */
    public static function validate(array $document, ?string $expectedTenantId = null): void
    {
        JsonPayloadValidator::requireKeys($document, ['schemaVersion', 'kind', 'instance']);
        JsonPayloadValidator::assertSchemaVersion(
            (string) $document['schemaVersion'],
            OperationalStateProcessor::SCHEMA_VERSION
        );

        if (!\is_array($document['instance'])) {
            throw new ValidationException('instance must be an object');
        }

        JsonPayloadValidator::requireNonEmptyString($document['instance'], 'tenantId');

        if (null !== $expectedTenantId && $document['instance']['tenantId'] !== $expectedTenantId) {
            throw new ValidationException('tenantId mismatch for this deployment');
        }
    }
}
