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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Filter\Converter;

use Exception;
use Magento\AdobeCommerceWebhooks\Model\Filter\Converter\ConverterFactory;
use Magento\AdobeCommerceWebhooks\Model\Filter\Converter\FieldConverterInterface;
use Magento\AdobeCommerceWebhooks\Model\Filter\Converter\HookFieldConverter;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see ConverterFactory
 */
class ConverterFactoryTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|MockObject
     */
    private ObjectManagerInterface|MockObject $objectManagerMock;

    /**
     * @var ConverterFactory
     */
    private ConverterFactory $converterFactory;

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->converterFactory = new ConverterFactory(
            $this->objectManagerMock
        );
    }

    public function testCreate()
    {
        $className = 'ConverterClass';
        $converterClassMock = $this->createMock(FieldConverterInterface::class);

        $this->objectManagerMock->expects(self::once())
            ->method('get')
            ->with($className)
            ->willReturn($converterClassMock);

        $this->assertEquals($converterClassMock, $this->converterFactory->create($className));
    }

    public function testCannotCreateConverterClass()
    {
        $exceptionMessage = 'Class not found';
        $className = 'ConverterClass';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Can\'t create field converter class "ConverterClass": "Class not found"');

        $this->objectManagerMock->expects(self::once())
            ->method('get')
            ->with($className)
            ->willThrowException(new Exception($exceptionMessage));

        $this->converterFactory->create($className);
    }

    public function testConverterDoesNotImplementInterface()
    {
        $className = 'ConverterClass';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Converter class "ConverterClass" does not implement "%s"',
            FieldConverterInterface::class
        ));

        $this->objectManagerMock->expects(self::once())
            ->method('get')
            ->with($className)
            ->willReturn($this->createMock(HookFieldConverter::class));

        $this->converterFactory->create($className);
    }
}
