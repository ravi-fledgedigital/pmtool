<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Plugin\Model\Quote;

use Magento\GiftWrapping\Plugin\Model\Quote\MergeQuoteItems;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MergeQuoteItemsTest extends TestCase
{
    /**
     * @var MergeQuoteItems
     */
    private $plugin;

    /**
     * @var Processor|MockObject
     */
    private $processorMock;

    /**
     * @var Item|MockObject
     */
    private $resultMock;

    /**
     * @var Item|MockObject
     */
    private $sourceMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->plugin = new MergeQuoteItems();
        $this->processorMock = $this->createMock(Processor::class);
        $this->resultMock = $this->getMockBuilder(Item::class)
            ->addMethods(['setGwId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceMock = $this->getMockBuilder(Item::class)
            ->addMethods(['getGwId'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test case when a source item has a Gift wrapping.
     */
    public function testAfterMergeExpectsSetGiftMessageIdCalled(): void
    {
        $giftWrappingId = 1;
        $this->sourceMock->expects($this->once())
            ->method('getGwId')
            ->willReturn($giftWrappingId);
        $this->resultMock->expects($this->once())
            ->method('setGwId')
            ->with($giftWrappingId);

        $this->assertSame(
            $this->resultMock,
            $this->plugin->afterMerge($this->processorMock, $this->resultMock, $this->sourceMock)
        );
    }

    /**
     * Test case when a source item doesn't have a Gift wrapping.
     */
    public function testAfterMergeWithoutGiftMessageId(): void
    {
        $this->sourceMock->expects($this->once())->method('getGwId')->willReturn(null);
        $this->resultMock->expects($this->never())->method('setGwId');

        $this->assertSame(
            $this->resultMock,
            $this->plugin->afterMerge($this->processorMock, $this->resultMock, $this->sourceMock)
        );
    }
}
