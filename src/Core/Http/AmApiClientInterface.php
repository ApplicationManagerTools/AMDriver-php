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
}
