<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Http;

use ApplicationManagerTools\AmDriver\Core\Dto\ConsumptionWebhookEvent;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;

interface AmApiClientInterface
{
    /**
     * @return array{statusCode: int, body: string}
     */
    public function pushConsumption(ConsumptionWebhookEvent $event): array;

    /**
     * @return array{statusCode: int, body: string}
     */
    public function reportOrchestrationCallback(OrchestrationCallbackRequest $request): array;

    /**
     * Programme l’annulation d’abonnement Stripe en fin de période côté AM.
     *
     * @return array{statusCode: int, body: string}
     */
    public function cancelSubscription(string $instanceId): array;

    /**
     * Annule une résiliation d’abonnement programmée en fin de période côté AM.
     *
     * @return array{statusCode: int, body: string}
     */
    public function resumeSubscription(string $instanceId): array;

    /**
     * Crée une session Customer Portal Stripe (upgrade Embarquement → Navigation) côté AM.
     *
     * @return array{statusCode: int, body: string}
     */
    public function createSubscriptionUpgradeSession(string $instanceId, string $returnUrl): array;

    /**
     * Crée une Checkout Session Stripe de réabonnement (Navigation) côté AM.
     *
     * @return array{statusCode: int, body: string}
     */
    public function createSubscriptionResubscribeSession(string $instanceId, string $returnUrl): array;
}
