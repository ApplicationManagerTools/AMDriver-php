<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\TestSupport;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

final class RecordingHttpClient implements HttpClientInterface
{
    /** @var MockHttpClient */
    private $inner;

    /** @var string */
    public $method = '';

    /** @var string */
    public $url = '';

    /** @var array<string, mixed> */
    public $options = [];

    public function __construct()
    {
        $self = $this;
        $this->inner = new MockHttpClient(static function (string $method, string $url, array $options) use ($self): MockResponse {
            $self->method = $method;
            $self->url = $url;
            $self->options = $options;

            return new MockResponse('{"success":true}', ['http_code' => 202]);
        });
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->inner->request($method, $url, $options);
    }

    public function stream($responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->inner->stream($responses, $timeout);
    }

    public function withOptions(array $options): self
    {
        return $this;
    }
}
