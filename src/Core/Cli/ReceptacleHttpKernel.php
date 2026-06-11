<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Cli;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;
use Throwable;

/**
 * Minimal request dispatcher for `bin/am-driver serve` (PHP built-in server).
 */
final class ReceptacleHttpKernel
{
    /** @var OrchestrationCommandProcessor */
    private $orchestrationProcessor;

    /** @var OperationalStateProcessor */
    private $operationalStateProcessor;

    /** @var string */
    private $orchestrationPath;

    /** @var string */
    private $operationalStatePath;

    /** @var string */
    private $orchestrationToken;

    /** @var string */
    private $operationalStateToken;

    public function __construct(
        OrchestrationCommandProcessor $orchestrationProcessor,
        OperationalStateProcessor $operationalStateProcessor,
        string $orchestrationPath,
        string $operationalStatePath,
        string $orchestrationToken,
        string $operationalStateToken
    ) {
        $this->orchestrationProcessor = $orchestrationProcessor;
        $this->operationalStateProcessor = $operationalStateProcessor;
        $this->orchestrationPath = $orchestrationPath;
        $this->operationalStatePath = $operationalStatePath;
        $this->orchestrationToken = $orchestrationToken;
        $this->operationalStateToken = $operationalStateToken;
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
                return $this->handleOrchestration($body, $headers);
            }

            if ($path === $this->operationalStatePath) {
                return $this->handleOperationalState($body, $headers);
            }
        }

        if ('GET' === $method && '/' === $path) {
            return [200, json_encode(['service' => 'am-driver-receptacle'], JSON_THROW_ON_ERROR)];
        }

        return [404, json_encode(['error' => 'Not found'], JSON_THROW_ON_ERROR)];
    }

    /**
     * @param array<string, list<string>> $headers
     *
     * @return array{0: int, 1: string}
     */
    private function handleOrchestration(string $body, array $headers): array
    {
        if (!$this->tokenMatches($headers, 'X-Orchestration-Command-Token', $this->orchestrationToken)) {
            return [401, json_encode(['error' => 'Invalid token'], JSON_THROW_ON_ERROR)];
        }

        try {
            $command = OrchestrationCommand::fromArray(JsonPayloadValidator::parseJsonObject($body));
            $result = $this->orchestrationProcessor->process($command);

            return [$result['httpStatus'], json_encode(['accepted' => true], JSON_THROW_ON_ERROR)];
        } catch (ValidationException $e) {
            return [400, json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)];
        }
    }

    /**
     * @param array<string, list<string>> $headers
     *
     * @return array{0: int, 1: string}
     */
    private function handleOperationalState(string $body, array $headers): array
    {
        if (!$this->tokenMatches($headers, 'X-Instance-Operational-State-Token', $this->operationalStateToken)) {
            return [401, json_encode(['error' => 'Invalid token'], JSON_THROW_ON_ERROR)];
        }

        try {
            $this->operationalStateProcessor->process(JsonPayloadValidator::parseJsonObject($body));

            return [200, json_encode(['accepted' => true], JSON_THROW_ON_ERROR)];
        } catch (ValidationException $e) {
            return [400, json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)];
        } catch (Throwable $e) {
            return [500, json_encode(['error' => 'Transient error'], JSON_THROW_ON_ERROR)];
        }
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function tokenMatches(array $headers, string $name, string $expected): bool
    {
        $needle = strtolower($name);
        foreach ($headers as $key => $values) {
            if (strtolower((string) $key) === $needle) {
                return '' !== $expected && hash_equals($expected, (string) ($values[0] ?? ''));
            }
        }

        return false;
    }
}
