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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\WebhookRunner\Response;

use Magento\AdobeCommerceWebhooks\Model\Filter\Converter\HookFieldConverter;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationValueConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for @see OperationValueConverter
 */
class OperationValueConverterTest extends TestCase
{
    /**
     * @var HookFieldConverter|MockObject
     */
    private HookFieldConverter|MockObject $hookFieldConverterMock;

    /**
     * @var OperationValueConverter
     */
    private OperationValueConverter $operationValueConverter;

    protected function setUp(): void
    {
        $this->hookFieldConverterMock = $this->createMock(HookFieldConverter::class);
        $this->operationValueConverter = new OperationValueConverter(
            $this->hookFieldConverterMock
        );
    }

    public function testConvertWithFieldSource()
    {
        $value = 'inputValue';
        $path = 'key1/key2/key3';
        $hookFields = [
            new HookField(['name' => 'key1.key2']),
            new HookField(['name' => 'key', 'source' => 'key1.key2.key3', 'converter' => 'TestConverter'])
        ];
        $webhookData = ['key1' => 'value1', 'key2' => 'value2'];
        $outputValue = 'convertedValue';

        $this->hookFieldConverterMock->expects(self::once())
            ->method('convertFromExternalFormat')
            ->with($value, $hookFields[1], $webhookData)
            ->willReturn($outputValue);

        $this->assertEquals(
            $outputValue,
            $this->operationValueConverter->convert($value, $path, $hookFields, $webhookData)
        );
    }

    /**
     * @return void
     */
    #[DataProvider('convertWithoutOrEmptySourceDataProvider')]
    public function testConvertWithoutOrEmptySource(array $hookFieldData)
    {
        $value = 'inputValue';
        $path = 'firstKey/secondKey';
        $hookFields = [
            new HookField($hookFieldData),
            new HookField(['name' => 'key1.key2.key3'])
        ];
        $webhookData = ['key' => 'value'];
        $outputValue = 'convertedValue';

        $this->hookFieldConverterMock->expects(self::once())
            ->method('convertFromExternalFormat')
            ->with($value, $hookFields[0], $webhookData)
            ->willReturn($outputValue);

        $this->assertEquals(
            $outputValue,
            $this->operationValueConverter->convert($value, $path, $hookFields, $webhookData)
        );
    }

    /**
     * @return array
     */
    public static function convertWithoutOrEmptySourceDataProvider(): array
    {
        return [
            [
                ['name' => 'firstKey.secondKey', 'source' => '', 'converter' => 'TestConverter']
            ],
            [
                ['name' => 'firstKey.secondKey', 'source' => null, 'converter' => 'TestConverter']
            ],
            [
                ['name' => 'firstKey.secondKey', 'converter' => 'TestConverter']
            ]
        ];
    }

    /**
     * @param string $path
     * @param HookField[] $hookFields
     * @return void
     */
    #[DataProvider('noConversionProvider')]
    public function testNoConversion(string $path, array $hookFields)
    {
        $this->hookFieldConverterMock->expects(self::never())
            ->method('convertFromExternalFormat');

        self::assertEquals(
            'value',
            $this->operationValueConverter->convert('value', $path, $hookFields, ['key' => 'value'])
        );
    }

    public static function noConversionProvider(): array
    {
        return [
            'no converter for matching hook field' => [
                'path' => 'test/key',
                'hookFields' => [
                    new HookField(['name' => 'test', 'source' => 'key']),
                    new HookField(['name' => 'test.key'])
                ]
            ],
            'no matching hookField' => [
                'path' => 'test/key1/key2',
                'hookFields' => [
                    new HookField(['name' => 'test', 'source' => 'key']),
                    new HookField(['name' => 'test.key1'])
                ]
            ]
        ];
    }
}
