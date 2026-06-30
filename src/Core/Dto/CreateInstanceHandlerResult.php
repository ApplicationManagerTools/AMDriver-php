<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Dto;

final class CreateInstanceHandlerResult
{
    /** @var string|null */
    private $instanceLocation;

    /** @var string|null */
    private $startedAt;

    public function __construct(?string $instanceLocation = null, ?string $startedAt = null)
    {
        $this->instanceLocation = $instanceLocation;
        $this->startedAt = $startedAt;
    }

    public function instanceLocation(): ?string
    {
        return $this->instanceLocation;
    }

    public function startedAt(): ?string
    {
        return $this->startedAt;
    }
}
