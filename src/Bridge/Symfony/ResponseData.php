<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony;

use stdClass;

class ResponseData
{
    /** @var bool */
    public $success;

    /** @var stdClass|null */
    public $data;

    /** @var string */
    public $error;

    /** @var string */
    public $error_message;

    public function __construct(
        bool $success,
        ?stdClass $data = null,
        string $error = '',
        string $error_message = ''
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
        $this->error_message = $error_message;
    }
}
