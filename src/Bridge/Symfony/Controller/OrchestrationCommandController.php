<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\Controller;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Http\ApplicationTokenAuthenticator;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class OrchestrationCommandController
{
    /** @var OrchestrationCommandProcessor */
    private $processor;

    /** @var ApplicationTokenAuthenticator */
    private $authenticator;

    public function __construct(OrchestrationCommandProcessor $processor, string $applicationToken)
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
            $payload = JsonPayloadValidator::parseJsonObject((string) $request->getContent());
            $command = OrchestrationCommand::fromArray($payload);
            $result = $this->processor->process($command);

            return new JsonResponse(
                ['accepted' => true, 'alreadyProcessed' => $result['alreadyProcessed']],
                $result['httpStatus'],
            );
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
