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

namespace Magento\Rma\Test\Unit\Model;

use Magento\Rma\Helper\Data as RmaHelper;
use Magento\Framework\Escaper;
use Magento\Rma\Model\ResourceModel\ItemFactory;
use Magento\Rma\Model\ItemCountValidator;
use Magento\Rma\Model\Rma;
use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Sales\Model\Order;
use Magento\Rma\Model\ResourceModel\Item as ItemResource;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemCountValidatorTest extends TestCase
{
    /**
     * @var RmaHelper|MockObject
     */
    private RmaHelper $dataHelper;

    /**
     * @var ItemFactory|MockObject
     */
    private ItemFactory $itemFactory;

    /**
     * @var Escaper|MockObject
     */
    private Escaper $escaper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->dataHelper = $this->createMock(RmaHelper::class);
        $this->itemFactory = $this->createMOck(ItemFactory::class);
        $this->escaper = $this->createMock(Escaper::class);

        parent::setUp();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testWrongStatus(): void
    {
        $value = $this->createMock(Rma::class);
        $value->expects($this->any())->method('getEntityId')->willReturn(null);
        $value->expects($this->exactly(3))->method('getStatus')->willReturn('non existing status');

        $validator = new ItemCountValidator($this->dataHelper, $this->itemFactory, $this->escaper);
        $this->assertFalse($validator->isValid($value));
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testIsValidTrue(): void
    {
        $quantity = $itemId = $orderId = $orderItemId = 1;
        $productName = 'Product name';

        $item = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData', 'getProductName', 'getId', 'getOrigData'])
            ->getMockForAbstractClass();
        $item->expects($this->any())->method('getData')->willReturn($quantity);
        $item->expects($this->exactly(2))->method('getProductName')->willReturn($productName);
        $item->expects($this->any())->method('getId')->willReturn($itemId);
        $item->expects($this->exactly(4))->method('getStatus')->willReturn($quantity);
        $item->expects($this->any())
            ->method('getOrigData')
            ->with('status')
            ->willReturn(Status::STATE_AUTHORIZED);
        $item->expects($this->exactly(3))->method('getOrderItemId')->willReturn($itemId);
        $item->expects($this->once())->method('getQtyRequested')->willReturn($quantity);

        $orderItem = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAvailableQty'])
            ->onlyMethods(['getId', 'getName'])
            ->getMock();
        $orderItem->expects($this->once())->method('getId')->willReturn($orderItemId);
        $orderItem->expects($this->once())->method('getName')->willReturn($productName);
        $orderItem->expects($this->once())->method('getAvailableQty')->willReturn($quantity);
        $itemResource = $this->createMock(ItemResource::class);
        $itemResource->expects($this->once())
            ->method('getOrderItemsCollection')
            ->willReturn(new \ArrayIterator([$orderItem]));

        $this->itemFactory->expects($this->once())->method('create')->willReturn($itemResource);
        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('getId')->willReturn($orderId);

        $value = $this->getRmaMock([$item], $order);
        $value->expects($this->exactly(2))->method('getEntityId')->willReturn(1);

        $this->escaper->expects($this->exactly(2))->method('escapeHtml')->willReturn($productName);

        $validator = new ItemCountValidator($this->dataHelper, $this->itemFactory, $this->escaper);
        $this->assertTrue($validator->isValid($value));
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testIsValidAvailableQuantityError(): void
    {
        $quantity = $itemId = $orderId = $orderItemId = 1;
        $productName = 'Product name';

        $item = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData', 'getProductName', 'getId', 'getOrigData'])
            ->getMockForAbstractClass();
        $item->expects($this->exactly(5))
            ->method('getData')
            ->willReturnOnConsecutiveCalls(1, 2, 1, 1, 1);
        $item->expects($this->any())->method('getProductName')->willReturn($productName);
        $item->expects($this->any())->method('getId')->willReturn($itemId);
        $item->expects($this->exactly(4))->method('getStatus')->willReturn('authorized');
        $item->expects($this->exactly(3))->method('getOrderItemId')->willReturn($itemId);
        $item->expects($this->once())->method('getQtyRequested')->willReturn($quantity);

        $orderItem = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAvailableQty'])
            ->onlyMethods(['getId', 'getName'])
            ->getMock();
        $orderItem->expects($this->once())->method('getId')->willReturn($orderItemId);
        $orderItem->expects($this->once())->method('getName')->willReturn($productName);
        $orderItem->expects($this->once())->method('getAvailableQty')->willReturn($quantity);
        $itemResource = $this->createMock(ItemResource::class);
        $itemResource->expects($this->once())
            ->method('getOrderItemsCollection')
            ->willReturn(new \ArrayIterator([$orderItem]));

        $this->itemFactory->expects($this->once())->method('create')->willReturn($itemResource);
        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('getId')->willReturn($orderId);

        $value = $this->getRmaMock([$item], $order);
        $value->expects($this->exactly(2))->method('getEntityId')->willReturn(1);
        $this->escaper->expects($this->exactly(2))->method('escapeHtml')->willReturn($productName);

        $validator = new ItemCountValidator($this->dataHelper, $this->itemFactory, $this->escaper);
        $this->assertFalse($validator->isValid($value));
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testIsValidQuantityStatus(): void
    {
        $quantity = $itemId = $orderId = $orderItemId = 1;
        $productName = 'Product name';

        $item = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData', 'getProductName', 'getId', 'getOrigData'])
            ->getMockForAbstractClass();
        $item->expects($this->exactly(5))
            ->method('getData')
            ->willReturnOnConsecutiveCalls(1, 1, 1, 1, null);
        $item->expects($this->exactly(2))->method('getProductName')->willReturn($productName);
        $item->expects($this->any())->method('getId')->willReturn($itemId);
        $item->expects($this->exactly(4))->method('getStatus')->willReturn('received');
        $item->expects($this->any())
            ->method('getOrigData')
            ->with('status')
            ->willReturn('no status');
        $item->expects($this->exactly(3))->method('getOrderItemId')->willReturn($itemId);
        $item->expects($this->once())->method('getQtyRequested')->willReturn($quantity);

        $orderItem = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAvailableQty'])
            ->onlyMethods(['getId', 'getName'])
            ->getMock();
        $orderItem->expects($this->once())->method('getId')->willReturn($orderItemId);
        $orderItem->expects($this->once())->method('getName')->willReturn($productName);
        $orderItem->expects($this->once())->method('getAvailableQty')->willReturn($quantity);
        $itemResource = $this->createMock(ItemResource::class);
        $itemResource->expects($this->once())
            ->method('getOrderItemsCollection')
            ->willReturn(new \ArrayIterator([$orderItem]));

        $this->itemFactory->expects($this->once())->method('create')->willReturn($itemResource);
        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('getId')->willReturn($orderId);

        $value = $this->getRmaMock([$item], $order);
        $value->expects($this->exactly(2))->method('getEntityId')->willReturn(1);

        $this->escaper->expects($this->exactly(2))->method('escapeHtml')->willReturn($productName);

        $validator = new ItemCountValidator($this->dataHelper, $this->itemFactory, $this->escaper);
        $this->assertFalse($validator->isValid($value));
    }

    /**
     * @param array $items
     * @param MockObject $order
     * @return Rma&MockObject|MockObject
     */
    private function getRmaMock(array $items, MockObject $order)
    {
        $value = $this->getMockBuilder(Rma::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsUpdate'])
            ->onlyMethods(['getItems', 'getOrder', 'getEntityId'])
            ->getMock();
        $value->expects($this->any())->method('getItems')->willReturn($items);

        $value->expects($this->once())->method('getOrder')->willReturn($order);

        return $value;
    }
}
