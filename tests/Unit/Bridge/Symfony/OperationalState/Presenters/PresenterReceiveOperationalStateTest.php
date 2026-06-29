<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Bridge\Symfony\OperationalState\Presenters;

use ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState\ReceiveOperationalStateServiceResponse;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OperationalState\Presenters\PresenterReceiveOperationalState;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PresenterReceiveOperationalStateTest extends TestCase
{
    public function testPresenterMapsAcceptedResponse(): void
    {
        // Arrange
        $sut = new PresenterReceiveOperationalState();
        $response = new ReceiveOperationalStateServiceResponse(true, false);

        // Act
        $sut->write($response);
        $result = $sut->read();

        // Assert
        self::assertInstanceOf(stdClass::class, $result);
        self::assertTrue($result->accepted);
        self::assertFalse($result->duplicate);
    }
}
