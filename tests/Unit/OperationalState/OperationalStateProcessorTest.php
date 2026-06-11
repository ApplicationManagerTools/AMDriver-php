<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\OperationalState;

use ApplicationManagerTools\AmDriver\Core\Contract\OperationalStateReceiverInterface;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateReceiptStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\FileOperationalStateStore;
use ApplicationManagerTools\AmDriver\Core\OperationalState\OperationalStateProcessor;
use PHPUnit\Framework\TestCase;

final class OperationalStateProcessorTest extends TestCase
{
    public function testDuplicateCorrelationSkipsReceiverButPersists(): void
    {
        // Arrange
        $dir = sys_get_temp_dir().'/am-driver-state-'.uniqid('', true);
        $receiver = new class implements OperationalStateReceiverInterface {
            public $calls = 0;

            public function receive(array $document): void
            {
                ++$this->calls;
            }
        };
        $processor = new OperationalStateProcessor(
            new FileOperationalStateStore($dir.'/state'),
            new FileOperationalStateReceiptStore($dir.'/receipts'),
            null,
            $receiver,
        );
        $json = file_get_contents(dirname(__DIR__, 2).'/fixtures/instance-operational-state-am-minimal.json');
        self::assertNotFalse($json);
        /** @var array<string, mixed> $document */
        $document = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        // Act
        $first = $processor->process($document);
        $second = $processor->process($document);

        // Assert
        self::assertFalse($first['duplicate']);
        self::assertTrue($second['duplicate']);
        self::assertSame(1, $receiver->calls);
    }
}
