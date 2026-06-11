<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Orchestration;

use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StartInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Contract\StopInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCallbackRequest;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\HandlerFailedException;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use ApplicationManagerTools\AmDriver\Core\Http\AmApiClientInterface;
use ApplicationManagerTools\AmDriver\Core\Idempotency\IdempotencyStoreInterface;
use Throwable;

final class OrchestrationCommandProcessor
{
    /** @var CreateInstanceHandlerInterface */
    private $createHandler;

    /** @var StopInstanceHandlerInterface */
    private $stopHandler;

    /** @var StartInstanceHandlerInterface */
    private $startHandler;

    /** @var IdempotencyStoreInterface */
    private $idempotencyStore;

    /** @var AmApiClientInterface */
    private $amApiClient;

    public function __construct(
        CreateInstanceHandlerInterface $createHandler,
        StopInstanceHandlerInterface $stopHandler,
        StartInstanceHandlerInterface $startHandler,
        IdempotencyStoreInterface $idempotencyStore,
        AmApiClientInterface $amApiClient
    ) {
        $this->createHandler = $createHandler;
        $this->stopHandler = $stopHandler;
        $this->startHandler = $startHandler;
        $this->idempotencyStore = $idempotencyStore;
        $this->amApiClient = $amApiClient;
    }

    /**
     * @return array{httpStatus: int, alreadyProcessed: bool}
     */
    public function process(OrchestrationCommand $command): array
    {
        if ($this->idempotencyStore->has($command->idempotencyKey())) {
            return ['httpStatus' => 200, 'alreadyProcessed' => true];
        }

        try {
            if ($command->operation()->isCreate()) {
                $this->createHandler->handle($command);
            } elseif ($command->operation()->isStop()) {
                $this->stopHandler->handle($command);
            } elseif ($command->operation()->isStart()) {
                $this->startHandler->handle($command);
            } elseif ($command->operation()->isDestroy()) {
                throw new ValidationException('DESTROY_INSTANCE is not supported by am-driver v1; see docs/ECARTS-AM.md');
            } else {
                throw new ValidationException('Unsupported operation');
            }
        } catch (HandlerFailedException $e) {
            $this->reportCallback($command, $e->callbackStatus(), $e->getMessage());

            if (CallbackStatus::RETRYABLE_FAILURE === $e->callbackStatus()->toString()) {
                return ['httpStatus' => 500, 'alreadyProcessed' => false];
            }

            return ['httpStatus' => 400, 'alreadyProcessed' => false];
        } catch (ValidationException $e) {
            $this->reportCallback($command, CallbackStatus::failed(), $e->getMessage());

            return ['httpStatus' => 400, 'alreadyProcessed' => false];
        } catch (Throwable $e) {
            $this->reportCallback($command, CallbackStatus::retryableFailure(), $e->getMessage());

            return ['httpStatus' => 500, 'alreadyProcessed' => false];
        }

        $this->idempotencyStore->remember($command->idempotencyKey());
        $this->reportCallback($command, CallbackStatus::succeeded(), null);

        return ['httpStatus' => 200, 'alreadyProcessed' => false];
    }

    private function reportCallback(OrchestrationCommand $command, CallbackStatus $status, ?string $message): void
    {
        $this->amApiClient->reportOrchestrationCallback(
            new OrchestrationCallbackRequest($command->idempotencyKey(), $status, $message),
        );
    }
}
