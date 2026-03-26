<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Test\Unit\Model\Grid;

use Magento\CommerceBackendUix\Model\Grid\ColumnsDataFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Unit test class for ColumnsDataValidator class
 */
class ColumnsDataFormatterTest extends TestCase
{
    /**
     * @var ColumnsDataFormatter
     */
    private $columnsDataFormatter;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->columnsDataFormatter = new ColumnsDataFormatter();
    }

    /**
     * Test date validation when receiving a date in the supported ISO format
     *
     * @return void
     */
    public function testValidateISODates(): void
    {
        $this->columnsDataFormatter->validateDataType('2011-10-02T23:25:42+00:00', 'date');
        $this->expectException(\Exception::class);
        $this->columnsDataFormatter->validateDataType('2030-14-01T23:25:42+11:00', 'date');
    }

    /**
     * Test date validation when receiving a date in a not supported timestamp format
     *
     * @return void
     */
    public function testValidateTimestamps(): void
    {
        $this->expectException(\Exception::class);
        $this->columnsDataFormatter->validateDataType('1696518420', 'date');
    }

    /**
     * Test date validation when receiving an empty date value
     *
     * @return void
     */
    public function testValidateEmptyDates(): void
    {
        $this->expectException(\Exception::class);
        $this->columnsDataFormatter->validateDataType('', 'date');
    }

    /**
     * Test date validation when receiving a malicious injection script
     *
     * @return void
     */
    public function testValidateSecurityInjectionDates(): void
    {
        $this->expectException(\Exception::class);
        $this->columnsDataFormatter->validateDataType('OR 1=1', 'date');
    }

    /**
     * Test string data format
     *
     * @return void
     */
    public function testFormatStringType(): void
    {
        $this->assertSame(
            'test',
            $this->columnsDataFormatter->format('test', 'string')
        );
    }

    /**
     * Test boolean data format
     *
     * @return void
     */
    public function testFormatBoolType(): void
    {
        $this->assertSame(
            true,
            $this->columnsDataFormatter->format(true, 'boolean')
        );
    }

    /**
     * Test integer data format
     *
     * @return void
     */
    public function testFormatIntegerType(): void
    {
        $this->assertSame(
            1,
            $this->columnsDataFormatter->format(1, 'integer')
        );
    }

    /**
     * Test date data format
     *
     * @return void
     */
    public function testFormatDateType(): void
    {
        $this->assertSame(
            'Oct 02, 2011 11:25:42 PM',
            $this->columnsDataFormatter->format('2011-10-02T23:25:42+00:00', 'date')
        );
    }
}
