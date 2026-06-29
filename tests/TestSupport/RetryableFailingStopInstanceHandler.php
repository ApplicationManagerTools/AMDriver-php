<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\TestSupport;

use ApplicationManagerTools\AmDriver\Core\Contract\StopInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Exception\HandlerFailedException;
use ApplicationManagerTools\AmDriver\Core\Orchestration\CallbackStatus;

final class RetryableFailingStopInstanceHandler implements StopInstanceHandlerInterface
{
    public function handle(OrchestrationCommand $command): void
    {
        throw new HandlerFailedException(CallbackStatus::retryableFailure(), 'Transient handler failure');
    }
}
