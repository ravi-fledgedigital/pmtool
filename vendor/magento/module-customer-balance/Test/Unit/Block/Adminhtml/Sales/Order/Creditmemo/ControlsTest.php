<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Creditmemo\Controls;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\CustomerBalance\Helper\Data as CustomerBalanceData;

class ControlsTest extends TestCase
{
    /**
     * @var Creditmemo|MockObject
     */
    private $creditMemo;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var Registry|MockObject
     */
    private $registry;

    /**
     * @var Controls|MockObject
     */
    private $block;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $objects = [
            [
                CustomerBalanceData::class,
                $this->createMock(CustomerBalanceData::class)
            ]
        ];
        $objectManager->prepareObjectManager($objects);
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditMemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBaseRewardCurrencyAmount', 'getBaseCustomerBalanceReturnMax'])
            ->getMock();
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['registry'])
            ->getMock();
        $this->registry->method('registry')
            ->willReturn($this->creditMemo);

        $this->block = $this->getMockBuilder(Controls::class)
            ->addMethods(['_getCreditmemo'])
            ->setConstructorArgs([$this->context, $this->registry])
            ->getMock();
    }

    /**
     * Basic test of calculating a return value with reward currency
     */
    public function testGetReturnValue()
    {
        $this->creditMemo->method('getBaseRewardCurrencyAmount')
            ->willReturn(10);

        $this->creditMemo->method('getBaseCustomerBalanceReturnMax')
            ->willReturn(100);

        self::assertEquals(90, $this->block->getReturnValue(), "Final refund amount wrong");
    }

    /**
     * Test calculating return without reward balance
     */
    public function testGetReturnValueWithNoRewardBalance()
    {
        $this->creditMemo->method('getBaseRewardCurrencyAmount')
            ->willReturn(0);

        $this->creditMemo->method('getBaseCustomerBalanceReturnMax')
            ->willReturn(100);

        self::assertEquals(100, $this->block->getReturnValue(), "Final refund amount wrong");
    }

    /**
     * Test getting return balance with invalid rewards.
     */
    public function testGetReturnValueWithInvalidRewardBalance()
    {
        $this->creditMemo->method('getBaseRewardCurrencyAmount')
            ->willReturn(200);

        $this->creditMemo->method('getBaseCustomerBalanceReturnMax')
            ->willReturn(100);

        self::assertEquals(100, $this->block->getReturnValue(), "Final refund amount wrong");
    }

    /**
     * Checks a case when only Reward Points amount is used and Customer Balance should be 0.
     */
    public function testGetReturnValueWithEmptyCustomerBalance()
    {
        $this->creditMemo->method('getBaseRewardCurrencyAmount')
            ->willReturn(100);

        $this->creditMemo->method('getBaseCustomerBalanceReturnMax')
            ->willReturn(100);

        self::assertEquals(0, $this->block->getReturnValue(), "Final refund amount wrong");
    }
}
