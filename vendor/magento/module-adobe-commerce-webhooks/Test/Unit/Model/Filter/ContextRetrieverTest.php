<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Filter;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetriever as Retriever;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetrieverException;
use Magento\AdobeCommerceWebhooks\Model\DataConverter\ArgumentDataConverter;
use Magento\AdobeCommerceWebhooks\Model\Filter\ContextRetriever;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
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
    private Retriever $retrieverMock;

    /**
     * @var ArgumentDataConverter|MockObject
     */
    private ArgumentDataConverter|MockObject $argumentDataConverterMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->argumentDataConverterMock = $this->createMock(ArgumentDataConverter::class);
        $this->retrieverMock = $this->createMock(Retriever::class);
        $this->contextRetriever = new ContextRetriever(
            $this->retrieverMock,
            $this->argumentDataConverterMock,
            $this->loggerMock,
        );
    }

    public function testGetContextValue()
    {
        $source = 'context_test.get_value';

        $hookMock = $this->createMock(Hook::class);
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn('some_value');
        $this->loggerMock->expects(self::never())
            ->method('error');
        $this->argumentDataConverterMock->expects(self::never())
            ->method('convert');

        self::assertEquals(
            'some_value',
            $this->contextRetriever->getContextValue($source, $hookMock)
        );
    }

    public function testGetContextValueWithContextRetrieverException()
    {
        $source = 'context_test.get_value';

        $hookMock = $this->createMock(Hook::class);
        $exceptionMessage = 'Context retrieval failed';

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willThrowException(new ContextRetrieverException(__($exceptionMessage)));
        $this->argumentDataConverterMock->expects(self::never())
            ->method('convert');
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                $exceptionMessage,
                ['hook' => $hookMock, 'destination' => ['internal', 'external']]
            );

        self::assertNull($this->contextRetriever->getContextValue($source, $hookMock));
    }

    public function testGetContextValueWithObjectConversion()
    {
        $source = 'context_test.get_value';

        $hookMock = $this->createMock(Hook::class);
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
            ->method('error');
        $this->argumentDataConverterMock->expects(self::once())
            ->method('convert')
            ->with([$testClass])
            ->willReturn([[
                'data' => 'value'
            ]]);

        self::assertEquals(
            ['data' => 'value'],
            $this->contextRetriever->getContextValue($source, $hookMock)
        );
    }

    public function testGetContextValueWithArrayOfObjects()
    {
        $source = 'context_test.get_value';
        $hookMock = $this->createMock(Hook::class);

        $object1 = new class {
            public function getId(): int
            {
                return 1;
            }
        };
        $object2 = new class {
            public function getId(): int
            {
                return 2;
            }
        };

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn([$object1, $object2]);
        $this->loggerMock->expects(self::never())
            ->method('error');
        $this->argumentDataConverterMock->expects(self::exactly(2))
            ->method('convert')
            ->willReturnMap([
                [[$object1], [['id' => 1]]],
                [[$object2], [['id' => 2]]],
            ]);

        self::assertEquals(
            [['id' => 1], ['id' => 2]],
            $this->contextRetriever->getContextValue($source, $hookMock)
        );
    }

    public function testGetContextValueWithArrayOfMixedTypes()
    {
        $source = 'context_test.get_value';
        $hookMock = $this->createMock(Hook::class);

        $object = new class {
            public function getName(): string
            {
                return 'foo';
            }
        };

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn([$object, 'scalar_value', 42]);
        $this->loggerMock->expects(self::never())
            ->method('error');
        $this->argumentDataConverterMock->expects(self::once())
            ->method('convert')
            ->with([$object])
            ->willReturn([['name' => 'foo']]);

        self::assertEquals(
            [['name' => 'foo'], 'scalar_value', 42],
            $this->contextRetriever->getContextValue($source, $hookMock)
        );
    }

    public function testGetContextValueWithArrayOfObjectsConversionFailure()
    {
        $source = 'context_test.get_value';
        $hookMock = $this->createMock(Hook::class);

        $object = new class {
        };

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn([$object]);
        $this->argumentDataConverterMock->expects(self::once())
            ->method('convert')
            ->willThrowException(new \RuntimeException('Conversion error'));
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                sprintf('Context access with source \'%s\' can not be converted.', $source),
                ['hook' => $hookMock, 'destination' => ['internal', 'external']]
            );

        self::assertNull($this->contextRetriever->getContextValue($source, $hookMock));
    }
}
