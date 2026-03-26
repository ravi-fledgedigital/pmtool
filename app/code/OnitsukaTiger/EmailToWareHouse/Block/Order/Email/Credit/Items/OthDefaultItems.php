<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\EmailToWareHouse\Block\Order\Email\Credit\Items;

/**
 * Sales Order Email items default renderer
 *
 */
class OthDefaultItems extends \Magento\Sales\Block\Order\Email\Items\DefaultItems
{
    /**
     * @var static $productNo
     */
    public static $productNo = 1;

    /**
     * Get number in product No column function
     *
     * @return $this|int
     */
    public function getProductNo()
    {
        return static::$productNo++;
    }
}
