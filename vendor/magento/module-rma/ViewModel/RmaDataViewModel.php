<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Item;

/**
 * Rma View Model class
 */
class RmaDataViewModel implements ArgumentInterface
{
    /**
     * @var Data
     */
    private $rmaHelper;

    /**
     * @param Data $rmaHelper
     */
    public function __construct(
        Data $rmaHelper
    ) {
        $this->rmaHelper = $rmaHelper;
    }

    /**
     * Parses quantity depending on isQtyDecimal flag
     *
     * @param float $quantity
     * @param Item $item
     * @return int|float
     */
    public function parseQuantity(float $quantity, Item $item)
    {
        return $this->rmaHelper->parseQuantity($quantity, $item);
    }

    /**
     * Get Qty by status
     *
     * @param Item $item
     * @return int|float
     */
    public function getQty(Item $item)
    {
        return $this->rmaHelper->getQty($item);
    }
}
