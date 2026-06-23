<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Http;

use ApplicationManagerTools\AmDriver\Core\Contract\ConnectivityProbeInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final class HttpOrchestrationConnectivityProbe implements ConnectivityProbeInterface
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var float */
    private $timeoutSeconds;

    public function __construct(HttpClientInterface $httpClient, float $timeoutSeconds = 5.0)
    {
        $this->httpClient = $httpClient;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function probeOrchestrationRoute(string $orchestrationUrl, string $commandToken): array
    {
        $checkedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);

        try {
            $response = $this->httpClient->request('POST', $orchestrationUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    ApplicationTokenAuthenticator::HEADER_NAME => $commandToken,
                ],
                'body' => '{}',
                'timeout' => $this->timeoutSeconds,
            ]);
            $statusCode = $response->getStatusCode();
            if (401 === $statusCode) {
                return [
                    'status' => 'failed',
                    'message' => 'Orchestration command token rejected by receptacle route.',
                    'checkedAt' => $checkedAt,
                ];
            }
            if ($statusCode >= 500) {
                return [
                    'status' => 'degraded',
                    'message' => sprintf('Receptacle route returned server error (HTTP %d).', $statusCode),
                    'checkedAt' => $checkedAt,
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Receptacle route is reachable (validation or transport acceptance).',
                'checkedAt' => $checkedAt,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'message' => 'Cannot reach receptacle route: '.$e->getMessage(),
                'checkedAt' => $checkedAt,
            ];
        }
    }
}
