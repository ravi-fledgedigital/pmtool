<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataServices\Plugin\Checkout\CustomerData;

use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Quote\Model\Quote\Item;

class AddTopLevelSkuToItemData
{
    /**
     * Add top level sku to item data
     *
     * @param AbstractItem $subject
     * @param array $result
     * @param Item $item
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetItemData(
        AbstractItem $subject,
        array $result,
        Item $item
    ): array {
        $product = $item->getOptionByCode('product_type')?->getProduct() ?? $item->getProduct();
        $customOptions = $product->getCustomOptions();
        // remove custom options to make sure we get generic sku
        // performance can be affected if we try to reload the product here
        $product->setCustomOptions([]);
        $result['ds_top_level_sku'] = $product->getSku();
        $customOptions && $product->setCustomOptions($customOptions);
        return $result;
    }
}
