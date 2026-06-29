<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Bridge\Symfony\AbstractController;

use ApplicationManagerTools\AmDriver\Bridge\Symfony\AbstractController\AbstractController;
use ApplicationManagerTools\AmDriver\Core\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class AbstractControllerTest extends TestCase
{
    public function testWriteSuccessfulResponseReturnsJsonEnvelope(): void
    {
        // Arrange
        $controller = new ExposeSuccessfulAbstractController();
        $data = new stdClass();
        $data->accepted = true;

        // Act
        $response = $controller->exposeSuccessful($data, Response::HTTP_OK);
        $decoded = json_decode((string) $response->getContent(), true);

        // Assert
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($decoded['success']);
        self::assertTrue($decoded['data']['accepted']);
    }

    public function testWriteUnsuccessfulResponseReturnsJsonEnvelope(): void
    {
        // Arrange
        $controller = new ExposeUnsuccessfulAbstractController();

        // Act
        $response = $controller->exposeUnsuccessful(new ValidationException('bad'), Response::HTTP_BAD_REQUEST);
        $decoded = json_decode((string) $response->getContent(), true);

        // Assert
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertFalse($decoded['success']);
        self::assertNull($decoded['data']);
        self::assertSame('bad', $decoded['error_message']);
    }
}

final class ExposeSuccessfulAbstractController extends AbstractController
{
    public function exposeSuccessful(stdClass $data, int $statusCode): Response
    {
        return $this->writeSuccessfulResponse($data, $statusCode);
    }
}

final class ExposeUnsuccessfulAbstractController extends AbstractController
{
    public function exposeUnsuccessful(Throwable $e, int $statusCode): Response
    {
        return $this->writeUnsuccessfulResponse($e, $statusCode);
    }
}
