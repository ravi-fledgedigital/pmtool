<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\NetSuiteStoreShipping\Block\Order\Shipment\Items;

/**
 * Packing List items default renderer
 *
 */
class DefaultItem extends \Magento\Sales\Block\Order\Email\Items\DefaultItems
{
    /**
     * @var static $productNo
     */
    public static $productNo = 1;

    /**
     * Get number in product No column function
     * @return int
     */
    public function getProductNo(){
        return static::$productNo++;
    }
}
