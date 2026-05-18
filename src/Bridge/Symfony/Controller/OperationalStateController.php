<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\Controller;

use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Throwable;

#[AsController]
final class OperationalStateController
{
    /** @var OperationalStateProcessor */
    private $processor;

    /** @var string */
    private $expectedToken;

    public function __construct(OperationalStateProcessor $processor, string $operationalStateToken)
    {
        $this->processor = $processor;
        $this->expectedToken = $operationalStateToken;
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->tokenMatches($request)) {
            return new JsonResponse(['error' => 'Invalid operational state token'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $document = JsonPayloadValidator::parseJsonObject((string) $request->getContent());
            $this->processor->process($document);

            return new JsonResponse(['accepted' => true], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => 'Transient error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function tokenMatches(Request $request): bool
    {
        foreach (['X-AM-Application-Token', 'X-Instance-Operational-State-Token'] as $header) {
            $token = trim((string) $request->headers->get($header, ''));
            if ('' !== $token && hash_equals($this->expectedToken, $token)) {
                return true;
            }
        }

        return false;
    }
}
