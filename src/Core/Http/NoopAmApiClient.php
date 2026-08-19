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

    public function resumeSubscription(string $instanceId): array
    {
        return ['statusCode' => 202, 'body' => '{"success":true,"data":{"accepted":true,"cancelAtPeriodEnd":false,"periodEnd":"2026-12-31T00:00:00+00:00"}}'];
    }

    public function createSubscriptionUpgradeSession(
        string $instanceId,
        string $returnUrl,
        string $targetFormulaId,
    ): array {
        return ['statusCode' => 200, 'body' => '{"success":true,"data":{"url":"https://billing.stripe.com/session/test_upgrade","expiresAt":"2026-08-06T15:30:00+00:00"}}'];
    }

    public function createSubscriptionResubscribeSession(string $instanceId, string $returnUrl): array
    {
        return ['statusCode' => 200, 'body' => '{"success":true,"data":{"url":"https://checkout.stripe.com/c/pay/test_resubscribe","expiresAt":"2026-08-11T12:00:00+00:00"}}'];
    }
}
