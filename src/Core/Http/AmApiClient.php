<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Http;

use ApplicationManagerTools\AmDriver\Core\Dto\ConsumptionWebhookEvent;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;
use InvalidArgumentException;
use stdClass;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AmApiClient implements AmApiClientInterface
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var AmApiClientConfig */
    private $config;

    public function __construct(HttpClientInterface $httpClient, AmApiClientConfig $config)
    {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    public function pushConsumption(ConsumptionWebhookEvent $event): array
    {
        $url = $this->config->baseUrl().'/api/v1/orchestration/consumption-events';
        $options = [
            'headers' => $this->applicationHeaders(),
            'json' => $event->toArray(),
            'timeout' => $this->config->timeoutSeconds(),
        ];

        return $this->requestWithRetryOnServerError('POST', $url, $options, $this->config->consumptionMaxRetries());
    }

    public function reportOrchestrationCallback(OrchestrationCallbackRequest $request): array
    {
        $url = $this->config->baseUrl().'/api/v1/orchestration/commands/callbacks';

        return $this->request(
            'POST',
            $url,
            [
                'headers' => $this->applicationHeaders(),
                'json' => $request->toArray(),
                'timeout' => $this->config->timeoutSeconds(),
            ],
        );
    }

    public function cancelSubscription(string $instanceId): array
    {
        $trimmed = trim($instanceId);
        if ('' === $trimmed) {
            throw new InvalidArgumentException('instanceId cannot be empty.');
        }

        $url = $this->config->baseUrl().'/api/v1/instances/'.rawurlencode($trimmed).'/billing/cancel-subscription';

        return $this->request(
            'POST',
            $url,
            [
                'headers' => $this->applicationHeaders(),
                'json' => new stdClass(),
                'timeout' => $this->config->timeoutSeconds(),
            ],
        );
    }

    public function resumeSubscription(string $instanceId): array
    {
        $trimmed = trim($instanceId);
        if ('' === $trimmed) {
            throw new InvalidArgumentException('instanceId cannot be empty.');
        }

        $url = $this->config->baseUrl().'/api/v1/instances/'.rawurlencode($trimmed).'/billing/resume-subscription';

        return $this->request(
            'POST',
            $url,
            [
                'headers' => $this->applicationHeaders(),
                'json' => new stdClass(),
                'timeout' => $this->config->timeoutSeconds(),
            ],
        );
    }

    public function createSubscriptionUpgradeSession(string $instanceId, string $returnUrl): array
    {
        $trimmed = trim($instanceId);
        if ('' === $trimmed) {
            throw new InvalidArgumentException('instanceId cannot be empty.');
        }

        $trimmedReturn = trim($returnUrl);
        if ('' === $trimmedReturn) {
            throw new InvalidArgumentException('returnUrl cannot be empty.');
        }

        $url = $this->config->baseUrl().'/api/v1/instances/'.rawurlencode($trimmed).'/billing/upgrade-session';

        return $this->request(
            'POST',
            $url,
            [
                'headers' => $this->applicationHeaders(),
                'json' => ['returnUrl' => $trimmedReturn],
                'timeout' => $this->config->timeoutSeconds(),
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function applicationHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            ApplicationTokenAuthenticator::HEADER_NAME => $this->config->applicationToken(),
        ];
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{statusCode: int, body: string}
     */
    private function requestWithRetryOnServerError(string $method, string $url, array $options, int $maxRetries): array
    {
        $attempt = 0;
        while (true) {
            $result = $this->request($method, $url, $options);
            $status = $result['statusCode'];
            if ($status < 500 || $attempt >= $maxRetries) {
                return $result;
            }
            ++$attempt;
            usleep($this->config->consumptionRetryDelayMs() * 1000 * $attempt);
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{statusCode: int, body: string}
     */
    private function request(string $method, string $url, array $options): array
    {
        $response = $this->httpClient->request($method, $url, $options);

        return [
            'statusCode' => $response->getStatusCode(),
            'body' => $response->getContent(false),
        ];
    }
}
