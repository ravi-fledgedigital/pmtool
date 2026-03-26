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

use Magento\AdobeCommerceWebhooks\Model\Rule\Operator\EqualOperator;
use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EqualOperator
 */
class EqualOperatorTest extends TestCase
{
    /**
     * @throws OperatorException
     */
    public function testVerify()
    {
        $operator = new EqualOperator();

        self::assertTrue($operator->verify(3, '3'));
        self::assertTrue($operator->verify('3', '3'));
        self::assertTrue($operator->verify('3.0', '3'));
        self::assertTrue($operator->verify('3.0', '3.0'));
        self::assertFalse($operator->verify(3.01, '3.0'));
        self::assertFalse($operator->verify(null, '3.0'));
        self::assertFalse($operator->verify(false, '3.0'));
        self::assertFalse($operator->verify('', '3.0'));
    }

    public function testVerifyFieldValueArrayException()
    {
        $this->expectException(OperatorException::class);
        $this->expectExceptionMessage('Input data must be in string format');

        (new EqualOperator())->verify([3], '3');
    }
}
