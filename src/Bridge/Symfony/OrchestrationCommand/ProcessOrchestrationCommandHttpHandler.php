<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\OrchestrationCommand;

use ApplicationManagerTools\AmDriver\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand\ProcessOrchestrationCommandService;
use ApplicationManagerTools\AmDriver\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand\ProcessOrchestrationCommandServiceRequest;
use ApplicationManagerTools\AmDriver\Bridge\Http\ResponseEncoder;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState\AccessDeniedException;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OrchestrationCommand\Presenters\PresenterProcessOrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Http\ApplicationTokenAuthenticator;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;
use Symfony\Component\HttpFoundation\Response;

class ProcessOrchestrationCommandHttpHandler
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

    /**
     * @param array<string, list<string>> $headers
     *
     * @return array{status: int, body: string}
     */
    public function handle(string $body, array $headers): array
    {
        if (!$this->authenticator->matchesHeaderMap($headers)) {
            return ResponseEncoder::unsuccessful(
                new AccessDeniedException('Invalid application token'),
                Response::HTTP_UNAUTHORIZED,
            );
        }

        try {
            $payload = JsonPayloadValidator::parseJsonObject($body);
            $command = OrchestrationCommand::fromArray($payload);

            $presenter = new PresenterProcessOrchestrationCommand();
            $service = new ProcessOrchestrationCommandService($this->processor, $presenter);
            $service->execute(new ProcessOrchestrationCommandServiceRequest($command));

            $httpStatus = $service->getResponse()->httpStatus;
            if ($httpStatus >= 400) {
                return ResponseEncoder::unsuccessful(
                    new OrchestrationCommandRejectedException('Orchestration command rejected'),
                    $httpStatus,
                );
            }

            return ResponseEncoder::successful($presenter->read(), $httpStatus);
        } catch (ValidationException $e) {
            return ResponseEncoder::unsuccessful($e, Response::HTTP_BAD_REQUEST);
        }
    }
}
