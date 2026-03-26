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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Rule;

use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorException;
use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorFactory;
use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see OperatorFactory
 */
class OperatorFactoryTest extends TestCase
{
    public function testCreate()
    {
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorFactory = new OperatorFactory([
            'equal' => $operatorMock
        ]);

        self::assertEquals($operatorMock, $operatorFactory->create('equal'));
    }

    public function testCreateOperatorNotExists()
    {
        $this->expectException(OperatorException::class);
        $this->expectExceptionMessage('Operator notEqual is not registered');

        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorFactory = new OperatorFactory([
            'equal' => $operatorMock
        ]);

        $operatorFactory->create('notEqual');
    }
}
