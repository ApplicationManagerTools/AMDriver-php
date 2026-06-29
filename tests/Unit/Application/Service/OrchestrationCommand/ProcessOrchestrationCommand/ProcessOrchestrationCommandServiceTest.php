<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand;

use ApplicationManagerTools\AmDriver\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand\ProcessOrchestrationCommandService;
use ApplicationManagerTools\AmDriver\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand\ProcessOrchestrationCommandServiceRequest;
use ApplicationManagerTools\AmDriver\Application\Service\OrchestrationCommand\ProcessOrchestrationCommand\ProcessOrchestrationCommandServiceResponse;
use ApplicationManagerTools\AmDriver\Application\Service\Shared\PresenterInterface;
use ApplicationManagerTools\AmDriver\Application\Service\Shared\Response;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\CommandCallLog;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingCreateInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingStartInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Cli\InMemory\LoggingStopInstanceHandler;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;
use ApplicationManagerTools\AmDriver\Core\Http\NoopAmApiClient;
use ApplicationManagerTools\AmDriver\Core\Idempotency\FileIdempotencyStore;
use ApplicationManagerTools\AmDriver\Core\Idempotency\FileOrchestrationCommandLifecycleStore;
use ApplicationManagerTools\AmDriver\Core\Orchestration\OrchestrationCommandProcessor;
use ApplicationManagerTools\AmDriver\Core\Orchestration\UnconfiguredDeferredCreateInstanceDispatcher;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ProcessOrchestrationCommandServiceTest extends TestCase
{
    public function testExecuteDelegatesToProcessorAndWritesPresenter(): void
    {
        // Arrange
        $dataDir = sys_get_temp_dir().'/am-driver-service-'.uniqid('', true);
        $command = OrchestrationCommand::fromArray(json_decode(
            (string) file_get_contents(dirname(__DIR__, 5).'/fixtures/orchestration-command-create.json'),
            true,
        ));
        $processor = new OrchestrationCommandProcessor(
            new LoggingCreateInstanceHandler(new CommandCallLog()),
            new LoggingStopInstanceHandler(new CommandCallLog()),
            new LoggingStartInstanceHandler(new CommandCallLog()),
            new FileIdempotencyStore($dataDir.'/idempotency'),
            new NoopAmApiClient(),
            new FileOrchestrationCommandLifecycleStore($dataDir.'/lifecycle'),
            new UnconfiguredDeferredCreateInstanceDispatcher(),
        );
        $presenter = new RecordingPresenter();
        $service = new ProcessOrchestrationCommandService($processor, $presenter);

        // Act
        $service->execute(new ProcessOrchestrationCommandServiceRequest($command));
        $service->execute(new ProcessOrchestrationCommandServiceRequest($command));

        // Assert
        self::assertSame(200, $service->getResponse()->httpStatus);
        self::assertTrue($service->getResponse()->alreadyProcessed);
        self::assertInstanceOf(ProcessOrchestrationCommandServiceResponse::class, $presenter->written);
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
