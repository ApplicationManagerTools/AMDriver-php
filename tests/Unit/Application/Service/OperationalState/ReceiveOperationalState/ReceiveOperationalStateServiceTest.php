<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Application\Service\OperationalState\ReceiveOperationalState;

use ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState\ReceiveOperationalStateService;
use ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState\ReceiveOperationalStateServiceRequest;
use ApplicationManagerTools\AmDriver\Application\Service\OperationalState\ReceiveOperationalState\ReceiveOperationalStateServiceResponse;
use ApplicationManagerTools\AmDriver\Application\Service\Shared\PresenterInterface;
use ApplicationManagerTools\AmDriver\Application\Service\Shared\Response;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateReceiptStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ReceiveOperationalStateServiceTest extends TestCase
{
    public function testExecuteDelegatesToProcessorAndWritesPresenter(): void
    {
        // Arrange
        $dataDir = sys_get_temp_dir().'/am-driver-service-'.uniqid('', true);
        $document = json_decode(
            (string) file_get_contents(dirname(__DIR__, 5).'/fixtures/instance-operational-state-am-minimal.json'),
            true,
        );
        self::assertIsArray($document);
        $processor = new OperationalStateProcessor(
            new FileOperationalStateStore($dataDir.'/operational-state'),
            new FileOperationalStateReceiptStore($dataDir.'/operational-state-receipts'),
        );
        $presenter = new RecordingPresenter();
        $service = new ReceiveOperationalStateService($processor, $presenter);
        $request = new ReceiveOperationalStateServiceRequest($document);

        // Act
        $service->execute($request);

        // Assert
        self::assertInstanceOf(ReceiveOperationalStateServiceResponse::class, $service->getResponse());
        self::assertTrue($service->getResponse()->accepted);
        self::assertFalse($service->getResponse()->duplicate);
        self::assertInstanceOf(ReceiveOperationalStateServiceResponse::class, $presenter->written);
    }
}

final class RecordingPresenter implements PresenterInterface
{
    /** @var Response|null */
    public $written;

    public function write(Response $response): void
    {
        $this->written = $response;
    }

    public function read(): stdClass
    {
        return new stdClass();
    }
}
