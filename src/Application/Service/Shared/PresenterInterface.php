<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Application\Service\Shared;

use stdClass;

interface PresenterInterface
{
    public function write(Response $response): void;

    public function read(): stdClass;
}
