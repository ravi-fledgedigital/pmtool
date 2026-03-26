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

namespace Magento\AdminUiSdkCustomFees\Observer;

use Magento\AdminUiSdkCustomFees\Api\CustomFeesRepositoryInterface;
use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Observer to save custom fee invoice info after invoice committed to database
 */
class UpdateCustomFeesInvoiceDataObserver implements ObserverInterface
{
    /**
     * @param Config $config
     * @param CustomFeesRepositoryInterface $customFeesRepository
     */
    public function __construct(
        private Config $config,
        private CustomFeesRepositoryInterface $customFeesRepository
    ) {
    }

    /**
     * Adds invoice generated id to custom fees database after invoice is saved
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        if (!$this->config->isAdminUISDKEnabled()) {
            return;
        }
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $extensionAttributes = $order->getExtensionAttributes();
        $customFees = $extensionAttributes->getCustomFees();
        foreach ($customFees as $customFee) {
            $this->customFeesRepository->addInvoicedAmount(
                (int)$customFee->getEntityId(),
                $invoice->getEntityId(),
                (float)$customFee[CustomFeesInterface::FIELD_FEE_AMOUNT_INVOICED],
                (float)$customFee[CustomFeesInterface::FIELD_BASE_FEE_AMOUNT_INVOICED]
            );
        }
    }
}
