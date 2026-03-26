<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2026 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Context;

use Magento\AdobeCommerceEventsClient\Event\Context\ContextRetriever;
use Magento\AdobeCommerceEventsClient\Event\Converter\EventDataConverter;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetriever as Retriever;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetrieverException;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see ContextRetriever
 */
class ContextRetrieverTest extends TestCase
{
    /**
     * @var ContextRetriever
     */
    private ContextRetriever $contextRetriever;

    /**
     * @var Retriever|MockObject
     */
    private Retriever|MockObject $retrieverMock;

    /**
     * @var EventDataConverter|MockObject
     */
    private EventDataConverter|MockObject $eventDataConverterMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->eventDataConverterMock = $this->createMock(EventDataConverter::class);
        $this->retrieverMock = $this->createMock(Retriever::class);
        $this->contextRetriever = new ContextRetriever(
            $this->retrieverMock,
            $this->eventDataConverterMock,
            $this->loggerMock,
        );
    }

    public function testGetContextValue()
    {
        $source = 'context_test.get_value';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn('some_value');
        $this->loggerMock->expects(self::never())
            ->method('warning');
        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');

        self::assertEquals(
            'some_value',
            $this->contextRetriever->getContextValue($source, $eventMock)
        );
    }

    public function testGetContextValueWithContextRetrieverException()
    {
        $source = 'context_test.get_value';
        $eventName = 'test.event';
        $exceptionMessage = 'Context retrieval failed';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventName);
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willThrowException(new ContextRetrieverException(__($exceptionMessage)));
        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');
        $this->loggerMock->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to retrieve context value for event test.event: ' . $exceptionMessage,
                ['destination' => ['internal', 'external']]
            );

        self::assertNull($this->contextRetriever->getContextValue($source, $eventMock));
    }

    public function testGetContextValueWithObjectConversion()
    {
        $source = 'context_test.get_value';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');

        $testClass = new class {
            public function getData(): string
            {
                return 'value';
            }
        };
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn($testClass);
        $this->loggerMock->expects(self::never())
            ->method('warning');
        $this->eventDataConverterMock->expects(self::once())
            ->method('convert')
            ->with($testClass)
            ->willReturn(['data' => 'value']);

        self::assertEquals(
            ['data' => 'value'],
            $this->contextRetriever->getContextValue($source, $eventMock)
        );
    }

    public function testGetContextValueWithObjectConversionFailure()
    {
        $source = 'context_test.get_value';
        $eventName = 'test.event';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventName);

        $testClass = new class {
            public function getData(): string
            {
                return 'value';
            }
        };
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn($testClass);
        $this->eventDataConverterMock->expects(self::once())
            ->method('convert')
            ->with($testClass)
            ->willThrowException(new LocalizedException(__('Conversion failed')));
        $this->loggerMock->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to retrieve context value for event test.event: ' .
                'Can not convert context value retrieved with source \'context_test.get_value\'.',
                ['destination' => ['internal', 'external']]
            );

        self::assertNull($this->contextRetriever->getContextValue($source, $eventMock));
    }
}
