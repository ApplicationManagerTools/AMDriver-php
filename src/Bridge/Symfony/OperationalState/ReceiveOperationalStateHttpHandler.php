<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState;

use ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState\ReceiveOperationalStateService;
use ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState\ReceiveOperationalStateServiceRequest;
use ApplicationManagerTools\AmDriver\Bridge\Http\ResponseEncoder;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState\Presenters\PresenterReceiveOperationalState;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Http\ApplicationTokenAuthenticator;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use ApplicationManagerTools\AmDriver\Core\Validation\JsonPayloadValidator;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ReceiveOperationalStateHttpHandler
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
            $presenter = new PresenterReceiveOperationalState();
            $service = new ReceiveOperationalStateService($this->processor, $presenter);
            $service->execute(new ReceiveOperationalStateServiceRequest(
                JsonPayloadValidator::parseJsonObject($body),
            ));

            return ResponseEncoder::successful($presenter->read(), Response::HTTP_OK);
        } catch (ValidationException $e) {
            return ResponseEncoder::unsuccessful($e, Response::HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            return ResponseEncoder::unsuccessful(
                new RuntimeException('Transient error'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
