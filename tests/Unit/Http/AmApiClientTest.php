<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Http;

use ApplicationManagerTools\AmDriver\Core\Dto\ConsumptionWebhookEvent;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClient;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientConfig;
use ApplicationManagerTools\AmDriver\Core\Orchestration\CallbackStatus;
use ApplicationManagerTools\AmDriver\Tests\TestSupport\RecordingHttpClient;
use PHPUnit\Framework\TestCase;

final class AmApiClientTest extends TestCase
{
    public function testPushConsumptionUsesTokenHeader(): void
    {
        // Arrange
        $recording = new RecordingHttpClient();
        $api = new AmApiClient($recording, new AmApiClientConfig('https://am.example', 'secret-cons', 'secret-cb', 5.0, 0));

        // Act
        $response = $api->pushConsumption(new ConsumptionWebhookEvent(
            'am_ins_10000000-0000-4000-8000-000000000001',
            'seats',
            '12',
            '2026-05-14T12:00:00+00:00',
            'captain-learning'
        ));

        // Assert
        self::assertSame(202, $response['statusCode']);
        self::assertSame('POST', $recording->method);
        self::assertStringContainsString('/api/v1/orchestration/consumption-events', $recording->url);
        self::assertSame('secret-cons', $this->headerValue($recording->options, 'X-Consumption-Webhook-Token'));
    }

    public function testReportCallbackUsesCallbackToken(): void
    {
        // Arrange
        $recording = new RecordingHttpClient();
        $api = new AmApiClient($recording, new AmApiClientConfig('https://am.example', 'secret-cons', 'secret-cb'));

        // Act
        $response = $api->reportOrchestrationCallback(new OrchestrationCallbackRequest(
            'idem-key',
            CallbackStatus::succeeded()
        ));

        // Assert
        self::assertSame(202, $response['statusCode']);
        self::assertStringContainsString('/api/v1/orchestration/commands/callbacks', $recording->url);
        self::assertSame('secret-cb', $this->headerValue($recording->options, 'X-Orchestration-Callback-Token'));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function headerValue(array $options, string $name): ?string
    {
        $headers = $options['headers'] ?? [];
        if (!\is_array($headers)) {
            return null;
        }
        $needle = strtolower($name);
        foreach ($headers as $key => $value) {
            if (\is_int($key) && \is_string($value)) {
                $parts = explode(':', $value, 2);
                if (2 === \count($parts) && strtolower(trim($parts[0])) === $needle) {
                    return trim($parts[1]);
                }
                continue;
            }
            if (strtolower((string) $key) === $needle) {
                if (\is_array($value)) {
                    return (string) ($value[0] ?? null);
                }

                return (string) $value;
            }
        }

        return null;
    }
}
