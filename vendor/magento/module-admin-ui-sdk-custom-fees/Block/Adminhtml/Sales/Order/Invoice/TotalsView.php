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
 * Adminhtml sales order invoice totals view
 *
 * @api
 */
class TotalsView extends Template
{
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
     * @return TotalsView
     */
    public function initTotals(): TotalsView
    {
        if (!$this->config->isAdminUISDKEnabled()) {
            return $this;
        }
        $order = $this->getParentBlock()->getSource()->getOrder();
        $customFees = $order->getExtensionAttributes()->getCustomFees() ?? [];
        foreach ($customFees as $invoiceCustomFee) {
            if ($invoiceCustomFee[CustomFeesInterface::FIELD_FEE_AMOUNT_INVOICED] !== 0) {
                $total = $this->customFeeDataObjectFactory->create($invoiceCustomFee);
                $this->getParentBlock()->addTotalBefore($total, 'grand_total');
            }
        }
        return $this;
    }
}
