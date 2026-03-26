<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2021 Adobe
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

namespace Magento\GiftCardProductDataExporter\Model\Provider\Product;

use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Giftcard\Option as GiftCardOption;

/**
 * Gift card product unique identifier generator
 */
class OptionsUid
{
    /**
     * Get gift card option value uid based on selectable amount value
     *
     * @param string $optionValue
     * @return string
     */
    public function getOptionValueUid(string $optionValue): string
    {
        $optionDetails = [
            Giftcard::TYPE_GIFTCARD,
            GiftCardOption::KEY_AMOUNT,
            $optionValue
        ];
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return \base64_encode(\implode('/', $optionDetails));
    }

    /**
     * Get shopper input option uid based on option key
     *
     * @param string $optionKey
     * @return string
     */
    public function getShopperInputOptionUid(string $optionKey): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return base64_encode(implode('/', [
            Giftcard::TYPE_GIFTCARD,
            $optionKey
        ]));
    }
}
