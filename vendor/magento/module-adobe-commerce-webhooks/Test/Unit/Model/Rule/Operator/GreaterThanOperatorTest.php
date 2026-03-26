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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Rule\Operator;

use Magento\AdobeCommerceWebhooks\Model\Rule\Operator\GreaterThanOperator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see GreaterThanOperator
 */
class GreaterThanOperatorTest extends TestCase
{
    public function testVerify()
    {
        $operator = new GreaterThanOperator();

        self::assertTrue($operator->verify('4', '3'));
        self::assertTrue($operator->verify('3.01', '3'));
        self::assertTrue($operator->verify(3.1, '3'));
        self::assertTrue($operator->verify('23', '3.0'));
        self::assertTrue($operator->verify(3));
        self::assertFalse($operator->verify(3, '3.0'));
        self::assertFalse($operator->verify(2.99, '3.00'));
        self::assertFalse($operator->verify('2.99', '3.0'));
        self::assertFalse($operator->verify('0', '3.0'));
        self::assertFalse($operator->verify(-1, '3.0'));
    }
}
