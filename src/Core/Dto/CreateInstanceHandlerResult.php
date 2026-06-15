<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Dto;

final class CreateInstanceHandlerResult
{
    /** @var string|null */
    private $instanceLocation;

    public function __construct(?string $instanceLocation = null)
    {
        $this->instanceLocation = $instanceLocation;
    }

    public function instanceLocation(): ?string
    {
        return $this->instanceLocation;
    }
}
