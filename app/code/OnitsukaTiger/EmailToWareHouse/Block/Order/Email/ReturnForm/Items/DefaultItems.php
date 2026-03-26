<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\EmailToWareHouse\Block\Order\Email\ReturnForm\Items;

/**
 * Sales Order Email items default renderer
 *
 */
class DefaultItems extends \Magento\Sales\Block\Order\Email\Items\DefaultItems
{
    /**
     * @var static $productNo
     */
    public static $productNo = 1;

    /**
     * Get number in product No column function
     * @return int
     */
    public function getProductNo()
    {
        return static::$productNo++;
    }
}
