<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class AccessDeniedException extends AccessDeniedHttpException
{
}
