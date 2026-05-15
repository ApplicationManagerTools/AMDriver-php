<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Http;

use ApplicationManagerTools\AmDriver\Core\Http\HttpOrchestrationConnectivityProbe;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HttpOrchestrationConnectivityProbeTest extends TestCase
{
    public function testProbeReturnsOkWhenRouteRespondsWith400(): void
    {
        // Arrange
        $client = new MockHttpClient(static function (): MockResponse {
            return new MockResponse('{"error":"validation"}', ['http_code' => 400]);
        });
        $probe = new HttpOrchestrationConnectivityProbe($client);

        // Act
        $result = $probe->probeOrchestrationRoute(
            'http://localhost/internal/am/orchestration/commands',
            'valid-token',
        );

        // Assert
        self::assertSame('ok', $result['status']);
        self::assertArrayHasKey('checkedAt', $result);
    }

    public function testProbeReturnsFailedWhenTokenRejected(): void
    {
        // Arrange
        $client = new MockHttpClient(static function (): MockResponse {
            return new MockResponse('', ['http_code' => 401]);
        });
        $probe = new HttpOrchestrationConnectivityProbe($client);

        // Act
        $result = $probe->probeOrchestrationRoute('http://localhost/commands', 'bad-token');

        // Assert
        self::assertSame('failed', $result['status']);
    }
}
