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

use Magento\AdobeCommerceWebhooks\Model\Rule\Operator\IsEmptyOperator;
use Magento\AdobeCommerceWebhooks\Model\Rule\Operator\NotEmptyOperator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see NotEmptyOperator
 */
class NotEmptyOperatorTest extends TestCase
{
    public function testVerify()
    {
        $operator = new NotEmptyOperator(new IsEmptyOperator());

        self::assertFalse($operator->verify(''));
        self::assertFalse($operator->verify(null));
        self::assertFalse($operator->verify(""));
        self::assertTrue($operator->verify('test'));
        self::assertTrue($operator->verify('1'));
        self::assertTrue($operator->verify('empty'));
        self::assertTrue($operator->verify(true));
    }
}
