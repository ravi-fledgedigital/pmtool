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

namespace Magento\AdminUiSdkCustomFees\Model\Quote\Total;

use Magento\AdminUiSdkCustomFees\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Class CustomFee to handle totals in quote
 */
class CustomFee extends AbstractTotal
{
    private const ID = 'id';
    private const CODE = 'code';
    private const LABEL = 'label';
    private const ORDER_MINIMUM_AMOUNT = 'orderMinimumAmount';
    private const TITLE = 'title';
    private const VALUE = 'value';
    private const BASE_VALUE = 'base_value';
    private const ADMIN_UI_SDK_PREFIX = 'adminuisdk_';

    /**
     * @param Config $config
     * @param Cache $cache
     */
    public function __construct(private Config $config, private Cache $cache)
    {
    }

    /**
     * Collect totals process with custom fees
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return CustomFee
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): CustomFee {
        parent::collect($quote, $shippingAssignment, $total);
        if (count($shippingAssignment->getItems()) && $this->config->isAdminUISDKEnabled()) {
            $fees = $this->cache->getOrderCustomFees();
            $subtotal = $total->getTotalAmount('subtotal');
            $baseToQuoteRate = $quote->getBaseToQuoteRate() ?? 1;
            $applicableFees = [];
            foreach ($fees as $fee) {
                $baseCustomFeeAmount = $fee[self::VALUE];
                $customFeeAmount = $baseCustomFeeAmount * $baseToQuoteRate;
                $baseOrderMinimumAmount = $fee[self::ORDER_MINIMUM_AMOUNT] ?? 0;
                $orderMinimumAmount = $baseOrderMinimumAmount * $baseToQuoteRate;
                if ($orderMinimumAmount <= $subtotal) {
                    $total->setTotalAmount($fee[self::ID], $customFeeAmount);
                    $total->setBaseTotalAmount($fee[self::ID], $baseCustomFeeAmount);
                    $total->setCustomFeeLabel($fee[self::LABEL]);
                    $total->setCustomFeeAmount($customFeeAmount);
                    $total->setBaseCustomFeeAmount($baseCustomFeeAmount);
                    $quote->setCustomFeeLabel($fee[self::LABEL]);
                    $quote->setCustomFeeAmount($customFeeAmount);
                    $quote->setBaseCustomFeeAmount($baseCustomFeeAmount);
                    $applicableFees[$fee[self::ID]] = $fee;
                }
            }
            $quote->setCustomFees($applicableFees);
        }
        return $this;
    }

    /**
     * Fetch (retrieve data with custom fees as array)
     *
     * @param Quote $quote
     * @param Total $total
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(Quote $quote, Total $total): array
    {
        $result = [];
        if (!$this->config->isAdminUISDKEnabled()) {
            return $result;
        }
        $subtotal = $quote->getSubtotal();
        $fees = $this->cache->getOrderCustomFees();
        $baseToQuoteRate = $quote->getBaseToQuoteRate() ?? 1;
        foreach ($fees as $fee) {
            $baseCustomFeeAmount = $fee[self::VALUE];
            $customFeeAmount = $baseCustomFeeAmount * $baseToQuoteRate;
            $baseOrderMinimumAmount = $fee[self::ORDER_MINIMUM_AMOUNT] ?? 0;
            $orderMinimumAmount = $baseOrderMinimumAmount * $baseToQuoteRate;
            if ($orderMinimumAmount <= $subtotal) {
                $result[] = [
                    self::CODE => self::ADMIN_UI_SDK_PREFIX . $fee[self::ID],
                    self::TITLE => __($fee[self::LABEL]),
                    self::VALUE => $quote->getItemsCount() ? $customFeeAmount : 0,
                    self::BASE_VALUE => $quote->getItemsCount() ? $baseCustomFeeAmount : 0
                ];
            }
        }
        return $result;
    }
}
