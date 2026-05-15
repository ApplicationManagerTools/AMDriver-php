<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Exception;

use ApplicationManagerTools\AmDriver\Core\Orchestration\CallbackStatus;
use Throwable;

final class HandlerFailedException extends AmDriverException
{
    /** @var CallbackStatus */
    private $status;

    public function __construct(CallbackStatus $status, string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->status = $status;
    }

    public function callbackStatus(): CallbackStatus
    {
        return $this->status;
    }
}
