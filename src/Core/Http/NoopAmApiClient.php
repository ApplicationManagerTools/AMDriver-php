<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Http;

use ApplicationManagerTools\AmDriver\Core\Dto\ConsumptionWebhookEvent;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;

/**
 * Local/testing client that accepts all outbound AM calls without network I/O.
 */
final class NoopAmApiClient implements AmApiClientInterface
{
    public function pushConsumption(ConsumptionWebhookEvent $event): array
    {
        return ['statusCode' => 202, 'body' => '{"success":true}'];
    }

    public function reportOrchestrationCallback(OrchestrationCallbackRequest $request): array
    {
        return ['statusCode' => 202, 'body' => '{"success":true}'];
    }

    public function cancelSubscription(string $instanceId): array
    {
        return ['statusCode' => 202, 'body' => '{"success":true,"data":{"accepted":true,"cancelAtPeriodEnd":true,"periodEnd":"2026-12-31T00:00:00+00:00"}}'];
    }
}
