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

namespace Magento\AdminUiSdkCustomFees\Model\Invoice\Total;

use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\AdminUiSdkCustomFees\Model\CustomFeesRepository;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Adds custom fees to invoice
 */
class CustomFee extends AbstractTotal
{
    /**
     * @param InvoiceExtensionFactory $invoiceExtensionFactory
     * @param CustomFeesRepository $customFeesRepository
     * @param Config $config
     */
    public function __construct(
        private InvoiceExtensionFactory $invoiceExtensionFactory,
        private CustomFeesRepository  $customFeesRepository,
        private Config $config
    ) {
    }

    /**
     * Adds invoiced custom fees to database
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        if (!$this->config->isAdminUISDKEnabled()) {
            return $this;
        }

        $order = $invoice->getOrder();
        $customFees = $this->customFeesRepository->getByOrderId($order->getId());
        $customFeeAmounts = 0;
        $baseCustomFeeAmounts = 0;
        $invoicedCustomFees = [];
        foreach ($customFees as $customFee) {
            if ($this->shouldFeeBeAdded($customFee, $invoice)) {
                $customFeeAmount = $customFee[CustomFeesInterface::FIELD_FEE_AMOUNT];
                $baseCustomFeeAmount = $customFee[CustomFeesInterface::FIELD_BASE_FEE_AMOUNT];
                $customFee[CustomFeesInterface::FIELD_FEE_AMOUNT_INVOICED] = $customFeeAmount;
                $customFee[CustomFeesInterface::FIELD_BASE_FEE_AMOUNT_INVOICED] = $baseCustomFeeAmount;
                $invoicedCustomFees[] = $customFee;
                $customFeeAmounts += $customFeeAmount;
                $baseCustomFeeAmounts += $baseCustomFeeAmount;
            }
        }
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->invoiceExtensionFactory->create();
        }
        $extensionAttributes->setData('custom_fees', $invoicedCustomFees);
        $order->setExtensionAttributes($extensionAttributes);
        $invoice->setOrder($order);
        $invoice->setGrandTotal($invoice->getGrandTotal() + $customFeeAmounts);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseCustomFeeAmounts);

        return $this;
    }

    /**
     * Returns if the custom fee should be added in the current invoice
     *
     * @param CustomFeesInterface $customFee
     * @param Invoice $invoice
     * @return bool
     */
    private function shouldFeeBeAdded(CustomFeesInterface $customFee, Invoice $invoice): bool
    {
        if ($customFee[CustomFeesInterface::FIELD_APPLY_FEE_ON_LAST_INVOICE]) {
            return $invoice->isLast();
        }
        return $customFee[CustomFeesInterface::FIELD_FEE_AMOUNT_INVOICED] == 0;
    }
}
