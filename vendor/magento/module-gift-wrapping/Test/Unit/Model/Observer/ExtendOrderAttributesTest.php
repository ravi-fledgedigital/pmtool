<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Model\Observer\ExtendOrderAttributes;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test of order attributes extension observer.
 */
class ExtendOrderAttributesTest extends TestCase
{
    /**
     * @var ExtendOrderAttributes
     */
    protected $subject;

    /**
     * @var Observer|MockObject
     */
    protected $observerMock;

    /**
     * @var DataObject|MockObject
     */
    protected $eventMock;

    /**
     * @var \Magento\Sales\Model\Order|MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Quote\Model\Address|MockObject
     */
    protected $quoteAddressMock;

    /**
     * @var Quote|MockObject
     */
    protected $quoteMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->quoteAddressMock = $this->createPartialMock(Address::class, ['hasData', 'getData']);

        $this->orderMock = $this->createPartialMock(Order::class, ['getData', 'setData']);

        $this->quoteMock = $this->createPartialMock(Quote::class, ['getShippingAddress', 'getData']);
        $this->quoteMock->expects($this->atLeastOnce())->method('getShippingAddress')
            ->willReturn($this->quoteAddressMock);

        $this->eventMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerMock = $this->createPartialMock(Observer::class, ['getEvent']);

        $this->subject = $objectManager->getObject(ExtendOrderAttributes::class);
    }

    /**
     * @param array $paramsArray
     * @param array $valuesArray
     * @dataProvider gwIdProvider
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testExecute($paramsArray, $valuesArray): void
    {
        $this->quoteAddressMock->expects($this->any())->method('hasData')->willReturnCallback(
            function ($attribute) {
                return in_array($attribute, ['gw_id', 'gw_allow_gift_receipt', 'gw_base_price_incl_tax']);
            }
        );
        $this->quoteAddressMock
            ->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnCallback(function ($arg1) use ($paramsArray, $valuesArray) {
                if ($arg1 == $paramsArray[0]) {
                    return $valuesArray[0];
                } elseif ($arg1 == $paramsArray[1]) {
                    return $valuesArray[1];
                } elseif ($arg1 == ($paramsArray[2] ?? null)) {
                    return $valuesArray[2] ?? null;
                }
            });

        $this->orderMock
            ->method('setData')
            ->willReturnCallback(function ($arg1, $arg2) use ($paramsArray, $valuesArray) {
                if ($arg1 == $paramsArray[0] && $arg2 == $valuesArray[0]) {
                       return null;
                } elseif ($arg1 == $paramsArray[1] && $arg2 == $valuesArray[1]) {
                       return null;
                } elseif (isset($valuesArray[2]) && $arg1 == $paramsArray[2] && $arg2 == $valuesArray[2]) {
                       return null;
                }
            });

        $this->quoteMock->expects($this->once())
            ->method('getData')->with('gw_id')
            ->willReturn(isset($paramsArray[2]) ? $valuesArray[0] : null);

        $this->eventMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        $this->eventMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->subject->execute($this->observerMock);
    }

    /**
     * @return array
     */
    public static function gwIdProvider(): array
    {
        return [
            'set_gw_id' => [
                ['gw_id', 'gw_allow_gift_receipt', 'gw_base_price_incl_tax'],
                [1, 1, 25]
            ],
            'unset_gw_id' => [
                ['gw_allow_gift_receipt', 'gw_base_price_incl_tax'],
                [true, 25]
            ],
        ];
    }
}
