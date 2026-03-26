<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdminUiSdkCustomFees\Block\Adminhtml\Sales\Order\Invoice;

use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\AdminUiSdkCustomFees\Model\CustomFeeDataObjectFactory;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Adminhtml sales order invoice totals
 *
 * @api
 */
class Totals extends Template
{
    private const GRAND_TOTAL = 'grand_total';

    /**
     * @param Context $context
     * @param Config $config
     * @param CustomFeeDataObjectFactory $customFeeDataObjectFactory
     * @param array $data
     */
    public function __construct(
        private Context $context,
        private Config $config,
        private CustomFeeDataObjectFactory $customFeeDataObjectFactory,
        private array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Returns totals of invoice with custom fees
     *
     * @return $this
     */
    public function initTotals(): Totals
    {
        if (!$this->config->isAdminUISDKEnabled()) {
            return $this;
        }

        $order = $this->getParentBlock()->getSource()->getOrder();
        $customFees = $order->getExtensionAttributes()->getCustomFees() ?? [];
        $invoices = $order->getInvoiceCollection();
        $isLastInvoice = false;
        foreach ($invoices as $invoice) {
            $isLastInvoice = $invoice['entity_id'] === null && $invoice->isLast();
        }

        foreach ($customFees as $customFee) {
            if ($customFee[CustomFeesInterface::FIELD_APPLY_FEE_ON_LAST_INVOICE] && $isLastInvoice) {
                $this->displayCustomFee($customFee);
            } elseif (!$customFee[CustomFeesInterface::FIELD_APPLY_FEE_ON_LAST_INVOICE] && count($invoices) === 1) {
                $this->displayCustomFee($customFee);
            }
        }

        return $this;
    }

    /**
     * Add custom fee to total display
     *
     * @param CustomFeesInterface $customFee
     * @return void
     */
    private function displayCustomFee(CustomFeesInterface $customFee): void
    {
        $total = $this->customFeeDataObjectFactory->create($customFee);
        $this->getParentBlock()->addTotalBefore($total, self::GRAND_TOTAL);
    }
}
