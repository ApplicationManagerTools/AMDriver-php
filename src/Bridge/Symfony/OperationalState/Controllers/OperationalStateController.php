<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState\Controllers;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\AbstractController\AbstractController;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState\ReceiveOperationalStateHttpHandler;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class OperationalStateController extends AbstractController
{
    /** @var ReceiveOperationalStateHttpHandler */
    private $handler;

    public function __construct(OperationalStateProcessor $processor, string $applicationToken)
    {
        $this->handler = new ReceiveOperationalStateHttpHandler($processor, $applicationToken);
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
