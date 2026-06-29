<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Cli;

use ApplicationManagerTools\AmDriver\Bridge\Http\ResponseEncoder;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState\ReceiveOperationalStateHttpHandler;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OrchestrationCommand\ProcessOrchestrationCommandHttpHandler;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use stdClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Minimal request dispatcher for `bin/am-driver serve` (PHP built-in server).
 */
final class ReceptacleHttpKernel
{
    /** @var ProcessOrchestrationCommandHttpHandler */
    private $orchestrationHandler;

    /** @var ReceiveOperationalStateHttpHandler */
    private $operationalStateHandler;

    /** @var string */
    private $orchestrationPath;

    /** @var string */
    private $operationalStatePath;

    public function __construct(
        OrchestrationCommandProcessor $orchestrationProcessor,
        OperationalStateProcessor $operationalStateProcessor,
        string $orchestrationPath,
        string $operationalStatePath,
        string $applicationToken
    ) {
        $this->orchestrationHandler = new ProcessOrchestrationCommandHttpHandler(
            $orchestrationProcessor,
            $applicationToken,
        );
        $this->operationalStateHandler = new ReceiveOperationalStateHttpHandler(
            $operationalStateProcessor,
            $applicationToken,
        );
        $this->orchestrationPath = $orchestrationPath;
        $this->operationalStatePath = $operationalStatePath;
    }

    /**
     * @param array<string, list<string>> $headers
     *
     * @return array{0: int, 1: string}
     */
    public function handle(string $method, string $uri, string $body, array $headers): array
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        if ('POST' === $method) {
            if ($path === $this->orchestrationPath) {
                $result = $this->orchestrationHandler->handle($body, $headers);

                return [$result['status'], $result['body']];
            }

            if ($path === $this->operationalStatePath) {
                $result = $this->operationalStateHandler->handle($body, $headers);

                return [$result['status'], $result['body']];
            }
        }

        if ('GET' === $method && '/' === $path) {
            $data = new stdClass();
            $data->service = 'am-driver-receptacle';
            $result = ResponseEncoder::successful($data, 200);

            return [$result['status'], $result['body']];
        }

        $result = ResponseEncoder::unsuccessful(new NotFoundHttpException('Not found'), 404);

        return [$result['status'], $result['body']];
    }
}
