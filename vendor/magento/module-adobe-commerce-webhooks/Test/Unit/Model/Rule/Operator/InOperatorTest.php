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

use Magento\AdobeCommerceWebhooks\Model\Rule\Operator\InOperator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see InOperator
 */
class InOperatorTest extends TestCase
{
    public function testVerify()
    {
        $operator = new InOperator();

        self::assertTrue($operator->verify('3', '2,3,5'));
        self::assertTrue($operator->verify(3, '2,3,5'));
        self::assertTrue($operator->verify('complete', 'complete,pending'));
        self::assertFalse($operator->verify(3));
        self::assertFalse($operator->verify(3.01, '2,3,5'));
        self::assertFalse($operator->verify(null, '2,3,5'));
        self::assertFalse($operator->verify(false, '2,3,5'));
        self::assertFalse($operator->verify('test', '2,3,5'));
        self::assertFalse($operator->verify('reject', 'complete,pending'));
    }
}
