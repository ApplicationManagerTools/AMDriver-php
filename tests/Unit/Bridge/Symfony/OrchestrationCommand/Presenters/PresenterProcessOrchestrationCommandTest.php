<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Bridge\Symfony\OrchestrationCommand\Presenters;

use ApplicationManagerTools\AmDriver\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand\ProcessOrchestrationCommandServiceResponse;
use ApplicationManagerTools\AmDriver\Bridge\Symfony\OrchestrationCommand\Presenters\PresenterProcessOrchestrationCommand;
use PHPUnit\Framework\TestCase;

final class PresenterProcessOrchestrationCommandTest extends TestCase
{
    public function testPresenterMapsAcceptedResponse(): void
    {
        // Arrange
        $sut = new PresenterProcessOrchestrationCommand();
        $response = new ProcessOrchestrationCommandServiceResponse(200, false);

        // Act
        $sut->write($response);
        $result = $sut->read();

        // Assert
        self::assertTrue($result->accepted);
        self::assertFalse($result->alreadyProcessed);
    }

    public function testPresenterMapsIdempotentResponse(): void
    {
        // Arrange
        $sut = new PresenterProcessOrchestrationCommand();
        $response = new ProcessOrchestrationCommandServiceResponse(200, true);

        // Act
        $sut->write($response);
        $result = $sut->read();

        // Assert
        self::assertTrue($result->accepted);
        self::assertTrue($result->alreadyProcessed);
    }
}
