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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\WebhookRunner\Response\Operation;

use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation\ValueResolver;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationInterface;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for @see ValueResolver class
 */
class ValueResolverTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|MockObject
     */
    private ObjectManagerInterface|MockObject $objectManagerMock;

    /**
     * @var ValueResolver
     */
    private ValueResolver $valueResolver;

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->valueResolver = new ValueResolver($this->objectManagerMock);
    }

    public function testGetValue()
    {
        $this->objectManagerMock->expects(self::never())
            ->method('create');

        self::assertEquals(
            'test value',
            $this->valueResolver->resolve([OperationInterface::VALUE => 'test value'])
        );
    }

    public function testGetValueAsInstance()
    {
        $this->objectManagerMock->expects(self::once())
            ->method('create')
            ->with(DataObject::class, ['key' => 'value']);

        $this->valueResolver->resolve([
            OperationInterface::VALUE => ['key' => 'value'],
            OperationInterface::INSTANCE => DataObject::class
        ]);
    }
}
