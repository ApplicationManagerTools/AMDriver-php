<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Http;

final class AmApiClientConfig
{
    /** @var string */
    private $baseUrl;

    /** @var string */
    private $consumptionWebhookToken;

    /** @var string */
    private $orchestrationCallbackToken;

    /** @var float */
    private $timeoutSeconds;

    /** @var int */
    private $consumptionMaxRetries;

    /** @var int */
    private $consumptionRetryDelayMs;

    public function __construct(
        string $baseUrl,
        string $consumptionWebhookToken,
        string $orchestrationCallbackToken,
        float $timeoutSeconds = 10.0,
        int $consumptionMaxRetries = 3,
        int $consumptionRetryDelayMs = 500,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->consumptionWebhookToken = $consumptionWebhookToken;
        $this->orchestrationCallbackToken = $orchestrationCallbackToken;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->consumptionMaxRetries = $consumptionMaxRetries;
        $this->consumptionRetryDelayMs = $consumptionRetryDelayMs;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function consumptionWebhookToken(): string
    {
        return $this->consumptionWebhookToken;
    }

    public function orchestrationCallbackToken(): string
    {
        return $this->orchestrationCallbackToken;
    }

    public function timeoutSeconds(): float
    {
        return $this->timeoutSeconds;
    }

    public function consumptionMaxRetries(): int
    {
        return $this->consumptionMaxRetries;
    }

    public function consumptionRetryDelayMs(): int
    {
        return $this->consumptionRetryDelayMs;
    }
}
