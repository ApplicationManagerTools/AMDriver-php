<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Cli\InMemory;

use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\CreateInstanceHandlerResult;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use DateTimeImmutable;
use DateTimeZone;

final class LoggingCreateInstanceHandler implements CreateInstanceHandlerInterface
{
    /** @var CommandCallLog */
    private $log;

    public function __construct(CommandCallLog $log)
    {
        $this->log = $log;
    }

    public function handle(OrchestrationCommand $command): CreateInstanceHandlerResult
    {
        $this->log->add('CREATE_INSTANCE', $command);

        return new CreateInstanceHandlerResult(null, (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(\DATE_ATOM));
    }
}
