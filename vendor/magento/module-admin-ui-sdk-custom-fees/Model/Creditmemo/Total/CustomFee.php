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

namespace Magento\AdminUiSdkCustomFees\Model\Creditmemo\Total;

use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\AdminUiSdkCustomFees\Model\CustomFeesRepository;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Adds custom fees to credit memo
 */
class CustomFee extends AbstractTotal
{
    /**
     * @param CreditmemoExtensionFactory $creditmemoExtensionFactory
     * @param CustomFeesRepository $customFeesRepository
     * @param Cache $cache
     * @param Config $config
     */
    public function __construct(
        private CreditmemoExtensionFactory $creditmemoExtensionFactory,
        private CustomFeesRepository  $customFeesRepository,
        private Cache $cache,
        private Config $config
    ) {
    }

    /**
     * Adds credited custom fee to database
     *
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        if (!$this->config->isAdminUISDKEnabled()) {
            return $this;
        }

        $order = $creditmemo->getOrder();
        $customFees = $this->customFeesRepository->getByOrderId($order->getId());
        $customFeeAmounts = 0;
        $baseCustomFeeAmounts = 0;
        $creditmemoCustomFees = [];
        foreach ($customFees as $customFee) {
            if ($this->shouldFeeBeAdded($customFee, $creditmemo)) {
                $customFeeInvoiced = $customFee[CustomFeesInterface::FIELD_FEE_AMOUNT_INVOICED];
                $baseCustomFeeInvoiced = $customFee[CustomFeesInterface::FIELD_BASE_FEE_AMOUNT_INVOICED];
                $customFee[CustomFeesInterface::FIELD_FEE_AMOUNT_REFUNDED] = $customFeeInvoiced;
                $customFee[CustomFeesInterface::FIELD_BASE_FEE_AMOUNT_REFUNDED] = $baseCustomFeeInvoiced;
                $creditmemoCustomFees[] = $customFee;
                $customFeeAmounts += $customFeeInvoiced;
                $baseCustomFeeAmounts += $baseCustomFeeInvoiced;
            }
        }
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->creditmemoExtensionFactory->create();
        }
        $extensionAttributes->setData('custom_fees', $creditmemoCustomFees);
        $order->setExtensionAttributes($extensionAttributes);
        $creditmemo->setOrder($order);
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $customFeeAmounts);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseCustomFeeAmounts);

        return $this;
    }

    /**
     * Returns if the custom fee should be added in the current credit memo
     *
     * @param CustomFeesInterface $customFee
     * @param Creditmemo $creditmemo
     * @return bool
     */
    private function shouldFeeBeAdded(CustomFeesInterface $customFee, Creditmemo $creditmemo): bool
    {
        if ($customFee[CustomFeesInterface::FIELD_APPLY_FEE_ON_LAST_CREDITMEMO]) {
            return $creditmemo->isLast();
        }
        return $customFee[CustomFeesInterface::FIELD_FEE_AMOUNT_REFUNDED] == 0;
    }
}
