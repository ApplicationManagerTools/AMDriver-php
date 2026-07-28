<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Cli\InMemory;

use ApplicationManagerTools\AmDriver\Core\Contract\GetInfoInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

final class LoggingGetInfoInstanceHandler implements GetInfoInstanceHandlerInterface
{
    /** @var CommandCallLog */
    private $log;

    public function __construct(CommandCallLog $log)
    {
        $this->log = $log;
    }

    public function handle(OrchestrationCommand $command): array
    {
        $this->log->add('GET_INFO_INSTANCE', $command);

        return [];
    }
}
