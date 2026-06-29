<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Http;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\ResponseData;
use stdClass;
use Throwable;

final class ResponseEncoder
{
    public static function encode(ResponseData $envelope): string
    {
        return json_encode($envelope, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{status: int, body: string}
     */
    public static function successful(stdClass $data, int $statusCode): array
    {
        return [
            'status' => $statusCode,
            'body' => self::encode(new ResponseData(true, $data, '', '')),
        ];
    }

    /**
     * @return array{status: int, body: string}
     */
    public static function unsuccessful(Throwable $e, int $statusCode): array
    {
        return [
            'status' => $statusCode,
            'body' => self::encode(new ResponseData(false, null, get_class($e), $e->getMessage())),
        ];
    }
}
