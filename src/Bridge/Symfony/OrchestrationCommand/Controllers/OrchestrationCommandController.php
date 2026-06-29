<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\OrchestrationCommand\Controllers;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\AbstractController\AbstractController;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OrchestrationCommand\ProcessOrchestrationCommandHttpHandler;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class OrchestrationCommandController extends AbstractController
{
    /** @var ProcessOrchestrationCommandHttpHandler */
    private $handler;

    public function __construct(OrchestrationCommandProcessor $processor, string $applicationToken)
    {
        $this->handler = new ProcessOrchestrationCommandHttpHandler($processor, $applicationToken);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->handler->handle((string) $request->getContent(), $this->normalizeHeaders($request));

        return new JsonResponse(json_decode($result['body'], true), $result['status']);
    }

    /**
     * @return array<string, list<string>>
     */
    private function normalizeHeaders(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            if (!is_string($name)) {
                continue;
            }
            $normalized = [];
            foreach ((array) $values as $value) {
                if (is_string($value)) {
                    $normalized[] = $value;
                }
            }
            $headers[$name] = $normalized;
        }

        return $headers;
    }
}
