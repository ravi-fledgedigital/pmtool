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

use Magento\AdobeCommerceWebhooks\Model\Rule\Operator\RegexOperator;
use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see RegexOperator
 */
class RegexOperatorTest extends TestCase
{
    /**
     * @throws OperatorException
     */
    public function testVerify()
    {
        $operator = new RegexOperator();

        self::assertTrue($operator->verify('test', '/tes.*/'));
        self::assertTrue($operator->verify('pending', '/complete|pending/'));
        self::assertTrue($operator->verify('3333', '/\d+/'));
        self::assertTrue($operator->verify(3333, '/\d+/'));
        self::assertTrue($operator->verify(33.33, '/\d+/'));
        self::assertTrue($operator->verify('second test', '/^sec.*/'));
        self::assertFalse($operator->verify('second test', '/^tes.*/'));
    }

    public function testVerifyBrokenRegexExceptionRuleValueNotSet()
    {
        $this->expectException(OperatorException::class);
        $this->expectExceptionMessage('Regex operation failed:');

        (new RegexOperator())->verify('test');
    }
}
