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

namespace Magento\AdminUiSdkCustomFees\Model\Sales\Pdf;

use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\AdminUiSdkCustomFees\Model\CustomFeesRepository;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\Order\Pdf\Total\DefaultTotal;
use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;

/**
 * Custom fee total model for PDF
 */
class CustomFee extends DefaultTotal
{
    private const EVENT_OBJECT_INVOICE = 'invoice';
    private const EVENT_OBJECT_CREDITMEMO = 'creditmemo';

    /**
     * @param Data $taxHelper
     * @param Calculation $taxCalculation
     * @param CollectionFactory $ordersFactory
     * @param Config $config
     * @param CustomFeesRepository $customFeesRepository
     * @param array $data
     */
    public function __construct(
        Data $taxHelper,
        Calculation $taxCalculation,
        CollectionFactory $ordersFactory,
        private Config $config,
        private CustomFeesRepository $customFeesRepository,
        array $data = []
    ) {
        parent::__construct($taxHelper, $taxCalculation, $ordersFactory, $data);
    }

    /**
     * Get array of arrays with totals information for display in PDF
     *
     * @return array
     */
    public function getTotalsForDisplay(): array
    {
        if (!$this->config->isAdminUISDKEnabled()) {
            return [];
        }

        $order = $this->getOrder();
        $customFees = $this->customFeesRepository->getByOrderId($order->getId());
        $totals = [];
        foreach ($customFees as $customFee) {
            if ($this->shouldFeeBeAdded($customFee, $this->getSource())) {
                $amountInclTax = $order->formatPriceTxt($customFee[CustomFeesInterface::FIELD_FEE_AMOUNT]);
                $fontSize = $this->getFontSize() ?: 7;
                $totals[] = [
                    'amount' => $this->getAmountPrefix() . $amountInclTax,
                    'label' => $customFee[CustomFeesInterface::FIELD_FEE_LABEL] . ':',
                    'font_size' => $fontSize,
                ];
            }
        }

        return $totals;
    }

    /**
     * Check if we can display custom fee information in PDF
     *
     * @return bool
     */
    public function canDisplay(): bool
    {
        return true;
    }

    /**
     * Returns if the custom fee should be added in the current pdf
     *
     * @param CustomFeesInterface $customFee
     * @param AbstractModel $source
     * @return bool
     */
    private function shouldFeeBeAdded(CustomFeesInterface $customFee, AbstractModel $source): bool
    {
        $entityId = $source->getEntityId();
        $eventObject = $source->getEventObject();

        if ($eventObject === self::EVENT_OBJECT_INVOICE) {
            return $customFee[CustomFeesInterface::FIELD_INVOICE_ID] == $entityId;
        }

        if ($eventObject === self::EVENT_OBJECT_CREDITMEMO) {
            return $customFee[CustomFeesInterface::FIELD_CREDITMEMO_ID] == $entityId;
        }

        return false;
    }
}
