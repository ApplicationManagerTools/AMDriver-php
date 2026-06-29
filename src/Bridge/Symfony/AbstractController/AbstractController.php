<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\AbstractController;

use ApplicationManagerTools\AmDriver\Bridge\Http\ResponseEncoder;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AbstractController
{
    protected function writeSuccessfulResponse(stdClass $data, int $statusCode = Response::HTTP_OK): Response
    {
        $encoded = ResponseEncoder::successful($data, $statusCode);

        return new JsonResponse(json_decode($encoded['body'], true), $encoded['status']);
    }

    protected function writeUnsuccessfulResponse(Throwable $e, int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): Response
    {
        $encoded = ResponseEncoder::unsuccessful($e, $statusCode);

        return new JsonResponse(json_decode($encoded['body'], true), $encoded['status']);
    }
}
