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
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;
use Magento\Framework\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see HookFieldConverter
 */
class HookFieldConverterTest extends TestCase
{
    /**
     * @var ConverterFactory|MockObject
     */
    private ConverterFactory|MockObject $converterFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    /**
     * @var FieldConverterInterface|MockObject
     */
    private FieldConverterInterface|MockObject $converterClassMock;

    /**
     * @var HookField|MockObject
     */
    private HookField|MockObject $hookFieldMock;

    /**
     * @var HookFieldConverter
     */
    private HookFieldConverter $hookFieldConverter;

    protected function setUp(): void
    {
        $this->converterClassMock = $this->createMock(FieldConverterInterface::class);
        $this->hookFieldMock = $this->createMock(HookField::class);

        $this->converterFactoryMock = $this->createMock(ConverterFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->hookFieldConverter = new HookFieldConverter(
            $this->converterFactoryMock,
            $this->loggerMock
        );
    }

    public function testConvertToExternalFormat()
    {
        $converterClass = 'ConverterClass';
        $originalValue = 'original_value';
        $pluginData = ['test_key' => $originalValue];
        $convertedValue = 'converted_value';

        $this->setSuccessfulConverterCreationExpectations($converterClass);
        $this->hookFieldMock->expects(self::never())
            ->method('getName');
        $this->loggerMock->expects(self::never())
            ->method('error');

        $this->converterClassMock->expects(self::once())
            ->method('toExternalFormat')
            ->with($originalValue, $this->hookFieldMock, $pluginData)
            ->willReturn($convertedValue);
        $this->assertEquals(
            $convertedValue,
            $this->hookFieldConverter->convertToExternalFormat($originalValue, $this->hookFieldMock, $pluginData)
        );
    }

    public function testConvertFromExternalFormat()
    {
        $converterClass = 'ConverterClass';
        $operationValue = 'operation_value';
        $pluginData = ['test_key' => 'test_value'];
        $convertedValue = 'converted_value';

        $this->setSuccessfulConverterCreationExpectations($converterClass);
        $this->hookFieldMock->expects(self::never())
            ->method('getName');
        $this->loggerMock->expects(self::never())
            ->method('error');

        $this->converterClassMock->expects(self::once())
            ->method('fromExternalFormat')
            ->with($operationValue, $this->hookFieldMock, $pluginData)
            ->willReturn($convertedValue);
        $this->assertEquals(
            $convertedValue,
            $this->hookFieldConverter->convertFromExternalFormat($operationValue, $this->hookFieldMock, $pluginData)
        );
    }

    public function testConvertToExternalFormatInvalidArgument()
    {
        $fieldName = 'testField';
        $value = 'unconverted_value';
        $exceptionMessage = 'Class does not exist';
        $logMessage = 'Unable to apply the converter to hook field \'testField\'. Exception: ' . $exceptionMessage;

        $this->setInvalidArgumentExceptionExpectations($fieldName, $exceptionMessage, $logMessage);

        $this->assertEquals(
            $value,
            $this->hookFieldConverter->convertToExternalFormat($value, $this->hookFieldMock, [])
        );
    }

    public function testConvertFromExternalFormatInvalidArgument()
    {
        $fieldName = 'fieldOne';
        $value = 'unconverted_value';
        $exceptionMessage = 'Class not found';
        $logMessage = 'Unable to apply the converter for hook field \'fieldOne\' to the operation value. ' .
            'Exception: ' . $exceptionMessage;

        $this->setInvalidArgumentExceptionExpectations($fieldName, $exceptionMessage, $logMessage);

        $this->assertEquals(
            $value,
            $this->hookFieldConverter->convertFromExternalFormat($value, $this->hookFieldMock, [])
        );
    }

    public function testConvertToExternalFormatException()
    {
        $converterClass = 'ExceptionConverter';
        $fieldName = 'testField';
        $value = 'unconverted_value';
        $pluginData = ['test_key' => $value];
        $exceptionMessage = 'Undefined index';

        $this->setSuccessfulConverterCreationExpectations($converterClass);
        $this->converterClassMock->expects(self::once())
            ->method('toExternalFormat')
            ->with($value, $this->hookFieldMock, $pluginData)
            ->willThrowException(new Exception($exceptionMessage));
        $this->hookFieldMock->expects(self::once())
            ->method('getName')
            ->willReturn($fieldName);
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Field conversion failed for hook field \'testField\'. Error: ' . $exceptionMessage);

        $this->assertEquals(
            $value,
            $this->hookFieldConverter->convertToExternalFormat($value, $this->hookFieldMock, $pluginData)
        );
    }

    public function testConvertFromExternalFormatException()
    {
        $converterClass = 'ExceptionConverter';
        $fieldName = 'testField';
        $value = 'unconverted_value';
        $pluginData = ['test_key' => $value];
        $exceptionMessage = 'Undefined index';

        $this->setSuccessfulConverterCreationExpectations($converterClass);
        $this->converterClassMock->expects(self::once())
            ->method('fromExternalFormat')
            ->with($value, $this->hookFieldMock, $pluginData)
            ->willThrowException(new Exception($exceptionMessage));
        $this->hookFieldMock->expects(self::once())
            ->method('getName')
            ->willReturn($fieldName);
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Operation value conversion failed with the converter for hook field \'testField\'. ' .
                'Error: ' . $exceptionMessage);

        $this->assertEquals(
            $value,
            $this->hookFieldConverter->convertFromExternalFormat($value, $this->hookFieldMock, $pluginData)
        );
    }

    /**
     * Sets common expectations for tests where a converter instance is successfully created.
     *
     * @param string $converterClass
     * @return void
     */
    private function setSuccessfulConverterCreationExpectations(string $converterClass)
    {
        $this->hookFieldMock->expects(self::once())
            ->method('getConverter')
            ->willReturn($converterClass);
        $this->converterFactoryMock->expects(self::once())
            ->method('create')
            ->with($converterClass)
            ->willReturn($this->converterClassMock);
    }

    /**
     * Sets common expectations for tests where creating a converter instance results in an InvalidArgumentException.
     *
     * @param string $fieldName
     * @param string $exceptionMessage
     * @param string $logMessage
     * @return void
     */
    private function setInvalidArgumentExceptionExpectations(
        string $fieldName,
        string $exceptionMessage,
        string $logMessage
    ) {
        $converterClass = 'InvalidConverter';
        $this->hookFieldMock->expects(self::once())
            ->method('getConverter')
            ->willReturn($converterClass);
        $this->hookFieldMock->expects(self::once())
            ->method('getName')
            ->willReturn($fieldName);
        $this->converterFactoryMock->expects(self::once())
            ->method('create')
            ->with($converterClass)
            ->willThrowException(new InvalidArgumentException(__($exceptionMessage)));
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with($logMessage);
    }
}
