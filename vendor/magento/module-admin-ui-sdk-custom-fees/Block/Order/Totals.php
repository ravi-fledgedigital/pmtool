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

namespace Magento\AdminUiSdkCustomFees\Block\Order;

use Magento\AdminUiSdkCustomFees\Api\CustomFeesRepositoryInterface;
use Magento\AdminUiSdkCustomFees\Model\CustomFeeDataObjectFactory;
use Magento\AdminUiSdkCustomFees\Model\CustomFeesRepository;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class Totals to display custom fees in order totals
 */
class Totals extends Template
{
    /**
     * @param Context $context
     * @param Config $config
     * @param CustomFeesRepositoryInterface $customFeesRepository
     * @param CustomFeeDataObjectFactory $customFeeDataObjectFactory
     * @param array $data
     */
    public function __construct(
        private Context $context,
        private Config $config,
        private CustomFeesRepositoryInterface $customFeesRepository,
        private CustomFeeDataObjectFactory $customFeeDataObjectFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Returns totals of order with custom fees
     *
     * @return $this
     */
    public function initTotals(): \Magento\AdminUiSdkCustomFees\Block\Order\Totals
    {
        if (!$this->config->isAdminUISDKEnabled()) {
            return $this;
        }
        $orderId = $this->getParentBlock()->getSource()->getEntityId();
        $customFees = $this->customFeesRepository->getByOrderId($orderId);
        foreach ($customFees as $customFee) {
            $total = $this->customFeeDataObjectFactory->create($customFee);
            $this->getParentBlock()->addTotalBefore($total, 'grand_total');
        }
        return $this;
    }
}
