<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\DependencyInjection\AmDriverExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AmDriverBundle extends Bundle
{
    public function getPath(): string
    {
        return __DIR__;
    }

    public function getContainerExtension(): AmDriverExtension
    {
        return new AmDriverExtension();
    }
}
