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

namespace Magento\AdminUiSdkCustomFees\Test\Unit\Model;

use Magento\AdminUiSdkCustomFees\Model\CustomFees;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit test class for CustomFees class
 */
class CustomFeesTest extends TestCase
{
    /**
     * @var CustomFees
     */
    private CustomFees $customFees;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->customFees = $objectManager->getObject(CustomFees::class);
    }

    /**
     * Test if CustomFees model initializes correctly
     */
    public function testConstruct(): void
    {
        $this->assertInstanceOf(CustomFees::class, $this->customFees);
    }

    /**
     * Test getIdentities method
     */
    public function testGetIdentities(): void
    {
        $expectedIdentities = [$this->customFees->getId()];
        $this->assertEquals($expectedIdentities, $this->customFees->getIdentities());
    }

    /**
     * Test getId method
     */
    public function testGetId(): void
    {
        $this->customFees->setData(CustomFees::FIELD_ENTITY_ID, '1');
        $this->assertEquals('1', $this->customFees->getId());
    }

    /**
     * Test getOrderId and setOrderId methods
     */
    public function testGetSetOrderId(): void
    {
        $orderId = '1001';
        $this->customFees->setOrderId($orderId);
        $this->assertEquals($orderId, $this->customFees->getOrderId());
    }

    /**
     * Test getCustomFeeCode and setCustomFeeCode methods
     */
    public function testGetSetCustomFeeCode(): void
    {
        $customFeeCode = 'FEE001';
        $this->customFees->setCustomFeeCode($customFeeCode);
        $this->assertEquals($customFeeCode, $this->customFees->getCustomFeeCode());
    }

    /**
     * Test getCustomFeeLabel and setCustomFeeLabel methods
     */
    public function testGetSetCustomFeeLabel(): void
    {
        $customFeeLabel = 'Shipping Fee';
        $this->customFees->setCustomFeeLabel($customFeeLabel);
        $this->assertEquals($customFeeLabel, $this->customFees->getCustomFeeLabel());
    }

    /**
     * Test getCustomFeeAmount and setCustomFeeAmount methods
     */
    public function testGetSetCustomFeeAmount(): void
    {
        $customFeeAmount = 10.50;
        $this->customFees->setCustomFeeAmount($customFeeAmount);
        $this->assertEquals($customFeeAmount, $this->customFees->getCustomFeeAmount());
    }

    /**
     * Test getBaseCustomFeeAmount and setBaseCustomFeeAmount methods
     */
    public function testGetSetBaseCustomFeeAmount(): void
    {
        $baseCustomFeeAmount = 8.50;
        $this->customFees->setBaseCustomFeeAmount($baseCustomFeeAmount);
        $this->assertEquals($baseCustomFeeAmount, $this->customFees->getBaseCustomFeeAmount());
    }

    /**
     * Test getCustomFeeAmountInvoiced and setCustomFeeAmountInvoiced methods
     */
    public function testGetSetCustomFeeAmountInvoiced(): void
    {
        $customFeeAmountInvoiced = 5.00;
        $this->customFees->setCustomFeeAmountInvoiced($customFeeAmountInvoiced);
        $this->assertEquals($customFeeAmountInvoiced, $this->customFees->getCustomFeeAmountInvoiced());
    }

    /**
     * Test getBaseCustomFeeAmountInvoiced and setBaseCustomFeeAmountInvoiced methods
     */
    public function testGetSetBaseCustomFeeAmountInvoiced(): void
    {
        $baseCustomFeeAmountInvoiced = 4.00;
        $this->customFees->setBaseCustomFeeAmountInvoiced($baseCustomFeeAmountInvoiced);
        $this->assertEquals($baseCustomFeeAmountInvoiced, $this->customFees->getBaseCustomFeeAmountInvoiced());
    }

    /**
     * Test getCustomFeeAmountRefunded and setCustomFeeAmountRefunded methods
     */
    public function testGetSetCustomFeeAmountRefunded(): void
    {
        $customFeeAmountRefunded = 2.00;
        $this->customFees->setCustomFeeAmountRefunded($customFeeAmountRefunded);
        $this->assertEquals($customFeeAmountRefunded, $this->customFees->getCustomFeeAmountRefunded());
    }

    /**
     * Test getBaseCustomFeeAmountRefunded and setBaseCustomFeeAmountRefunded methods
     */
    public function testGetSetBaseCustomFeeAmountRefunded(): void
    {
        $baseCustomFeeAmountRefunded = 1.50;
        $this->customFees->setBaseCustomFeeAmountRefunded($baseCustomFeeAmountRefunded);
        $this->assertEquals($baseCustomFeeAmountRefunded, $this->customFees->getBaseCustomFeeAmountRefunded());
    }

    /**
     * Test isApplyFeeOnLastInvoice and setApplyFeeOnLastInvoice methods
     */
    public function testIsSetApplyFeeOnLastInvoice(): void
    {
        $applyFeeOnLastInvoice = true;
        $this->customFees->setApplyFeeOnLastInvoice($applyFeeOnLastInvoice);
        $this->assertEquals($applyFeeOnLastInvoice, $this->customFees->isApplyFeeOnLastInvoice());
    }

    /**
     * Test isApplyFeeOnLastCreditmemo and setApplyFeeOnLastCreditmemo methods
     */
    public function testIsSetApplyFeeOnLastCreditmemo(): void
    {
        $applyFeeOnLastCreditmemo = false;
        $this->customFees->setApplyFeeOnLastCreditmemo($applyFeeOnLastCreditmemo);
        $this->assertEquals($applyFeeOnLastCreditmemo, $this->customFees->isApplyFeeOnLastCreditmemo());
    }

    /**
     * Test getInvoiceId and setInvoiceId methods
     */
    public function testGetSetInvoiceId(): void
    {
        $invoiceId = 'INV001';
        $this->customFees->setInvoiceId($invoiceId);
        $this->assertEquals($invoiceId, $this->customFees->getInvoiceId());
    }

    /**
     * Test getCreditmemoId and setCreditmemoId methods
     */
    public function testGetSetCreditmemoId(): void
    {
        $creditmemoId = 'CM001';
        $this->customFees->setCreditmemoId($creditmemoId);
        $this->assertEquals($creditmemoId, $this->customFees->getCreditmemoId());
    }
}
