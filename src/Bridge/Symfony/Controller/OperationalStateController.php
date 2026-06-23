<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\Controller;

use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Http\ApplicationTokenAuthenticator;
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

    /** @var ApplicationTokenAuthenticator */
    private $authenticator;

    public function __construct(OperationalStateProcessor $processor, string $applicationToken)
    {
        $this->processor = $processor;
        $this->authenticator = new ApplicationTokenAuthenticator($applicationToken);
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->authenticator->matchesRequest($request)) {
            return new JsonResponse(['error' => 'Invalid application token'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $this->processor->process(JsonPayloadValidator::parseJsonObject((string) $request->getContent()));

            return new JsonResponse(['accepted' => true], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => 'Transient error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
