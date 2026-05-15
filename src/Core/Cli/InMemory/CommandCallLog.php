<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Cli\InMemory;

use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

final class CommandCallLog
{
    /** @var list<array{operation: string, command: array<string, mixed>}> */
    private $entries = [];

    public function add(string $operation, OrchestrationCommand $command): void
    {
        $this->entries[] = [
            'operation' => $operation,
            'command' => $command->toArray(),
        ];
    }

    /**
     * @return list<array{operation: string, command: array<string, mixed>}>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
