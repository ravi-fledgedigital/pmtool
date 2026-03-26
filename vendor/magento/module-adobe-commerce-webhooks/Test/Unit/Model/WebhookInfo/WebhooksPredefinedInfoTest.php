<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\WebhookInfo;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Converter\ClassToArrayConverterInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\AdobeCommerceWebhooks\Model\WebhookInfo\WebhooksPredefinedInfo;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see WebhooksPredefinedInfo
 */
class WebhooksPredefinedInfoTest extends TestCase
{
    /**
     * @var ClassToArrayConverterInterface|MockObject
     */
    private ClassToArrayConverterInterface|MockObject $classToArrayConverterMock;

    /**
     * @var Webhook|MockObject
     */
    private Webhook|MockObject $webhookMock;

    /**
     * @var WebhooksPredefinedInfo
     */
    private WebhooksPredefinedInfo $webhooksPredefinedInfo;

    protected function setUp(): void
    {
        $this->classToArrayConverterMock = $this->createMock(ClassToArrayConverterInterface::class);
        $this->webhookMock = $this->createMock(Webhook::class);

        $this->webhooksPredefinedInfo = new WebhooksPredefinedInfo(
            $this->classToArrayConverterMock
        );
    }

    public function testGetReturnsNullForNonExistentWebhook(): void
    {
        $this->webhookMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.non_existent_webhook');

        self::assertNull($this->webhooksPredefinedInfo->get($this->webhookMock));
    }

    public function testGetReturnsTransformedConfigForObserverWithClassType(): void
    {
        $this->webhookMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('observer.sales_quote_add_item');
        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with(Item::class, 2)
            ->willReturn(['id' => 'int', 'sku' => 'string']);

        $result = $this->webhooksPredefinedInfo->get($this->webhookMock);

        self::assertArrayHasKey('eventName', $result);
        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('quoteItem', $result['data']);
        self::assertArrayHasKey('id', $result['data']['quoteItem']);
        self::assertArrayHasKey('sku', $result['data']['quoteItem']);
    }

    public function testGetReturnsTransformedConfigForMultipleClassTypes(): void
    {
        $this->webhookMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('observer.sales_quote_merge_after');
        $this->classToArrayConverterMock->expects(self::exactly(2))
            ->method('convert')
            ->willReturnMap([
                [Quote::class, 2, ['id' => 'int']],
                [Quote::class, 2, ['id' => 'int']],
            ]);

        $result = $this->webhooksPredefinedInfo->get($this->webhookMock);

        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('id', $result['data']['quote']);
        self::assertArrayHasKey('id', $result['data']['source']);
    }

    public function testGetReturnsConfigWithMixedType(): void
    {
        $this->webhookMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('observer.checkout_cart_product_add_before');
        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with(Product::class, 2)
            ->willReturn(['id' => 'int', 'name' => 'string']);

        $result = $this->webhooksPredefinedInfo->get($this->webhookMock);

        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('info', $result['data']);
        self::assertEquals('mixed', $result['data']['info']);
        self::assertArrayHasKey('id', $result['data']['product']);
        self::assertArrayHasKey('name', $result['data']['product']);
    }

    public function testGetReturnsConfigWithObjectType(): void
    {
        $this->webhookMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('observer.sales_order_view_custom_attributes_update_before');
        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with(Order::class, 2)
            ->willReturn(['id' => 'int', 'status' => 'string']);

        $result = $this->webhooksPredefinedInfo->get($this->webhookMock);

        self::assertArrayHasKey('data', $result);
        self::assertEquals('object{}', $result['data']['custom_attributes']);
        self::assertArrayHasKey('id', $result['data']['order']);
    }
}
