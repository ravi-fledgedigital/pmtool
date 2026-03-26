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

use Magento\CommerceBackendUix\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Observer for adding custom fees to order before submitting quote
 */
class AddFeesToOrderObserver implements ObserverInterface
{
    /**
     * @param Config $config
     */
    public function __construct(private Config $config)
    {
    }

    /**
     * Adds custom fee to order when quote is saved
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer): AddFeesToOrderObserver
    {
        if ($this->config->isAdminUISDKEnabled()) {
            $quote = $observer->getEvent()->getQuote();
            $order = $observer->getEvent()->getOrder();

            $customFees = $quote->getCustomFees();
            $order->setData('custom_fees', $customFees);
        }

        return $this;
    }
}
