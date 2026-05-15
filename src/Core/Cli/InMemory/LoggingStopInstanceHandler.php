<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Cli\InMemory;

use ApplicationManagerTools\AmDriver\Core\Contract\StopInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

final class LoggingStopInstanceHandler implements StopInstanceHandlerInterface
{
    /** @var CommandCallLog */
    private $log;

    public function __construct(CommandCallLog $log)
    {
        $this->log = $log;
    }

    public function handle(OrchestrationCommand $command): void
    {
        $this->log->add('STOP_INSTANCE', $command);
    }
}
