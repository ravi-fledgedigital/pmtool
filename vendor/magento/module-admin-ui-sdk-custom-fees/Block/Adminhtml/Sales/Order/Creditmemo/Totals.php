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

namespace Magento\AdminUiSdkCustomFees\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\AdminUiSdkCustomFees\Model\CustomFeeDataObjectFactory;
use Magento\AdminUiSdkCustomFees\Model\CustomFeesRepository;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Adminhtml sales order credit memo totals
 *
 * @api
 */
class Totals extends Template
{
    /**
     * @param Context $context
     * @param Config $config
     * @param CustomFeesRepository $customFeesRepository
     * @param CustomFeeDataObjectFactory $customFeeDataObjectFactory
     * @param array $data
     */
    public function __construct(
        private Context $context,
        private Config $config,
        private CustomFeesRepository $customFeesRepository,
        private CustomFeeDataObjectFactory $customFeeDataObjectFactory,
        private array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Returns totals of credit memo with custom fee
     *
     * @return $this
     */
    public function initTotals(): Totals
    {
        if (!$this->config->isAdminUISDKEnabled()) {
            return $this;
        }

        $creditMemo = $this->getParentBlock()->getSource();
        $order = $creditMemo->getOrder();
        $customFees = $this->customFeesRepository->getByOrderId($order->getId());
        foreach ($customFees as $customFee) {
            if ($this->shouldFeeBeAdded($customFee, $creditMemo)) {
                $total = $this->customFeeDataObjectFactory->create($customFee);
                $this->getParentBlock()->addTotalBefore($total, 'grand_total');
            }
        }
        return $this;
    }

    /**
     * Returns if the custom fee should be added in the current credit memo
     *
     * @param CustomFeesInterface $customFee
     * @param Creditmemo $creditMemo
     * @return bool
     */
    private function shouldFeeBeAdded(CustomFeesInterface $customFee, Creditmemo $creditMemo): bool
    {
        if ($customFee->getCreditmemoId() !== null) {
            return $customFee->getCreditmemoId() === $creditMemo->getEntityId();
        }
        if ($customFee->isApplyFeeOnLastCreditmemo()) {
            return $creditMemo->isLast();
        }
        return $customFee->getCustomFeeAmountRefunded() == 0;
    }
}
