<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api\Data;

/**
 * Interface ReturnOrderItemInterface
 */
interface ReturnOrderItemInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const ITEM = 'item';
    public const PRODUCT_ITEM = 'product_item';
    public const AVAILABLE_QTY = 'available_qty';
    public const PURCHASED_QTY = 'purchased_qty';
    public const IS_RETURNABLE = 'is_returnable';
    public const NO_RETURNABLE_REASON = 'no_returnable_reason';
    public const NO_RETURNABLE_DATA = 'no_returnable_data';
    public const RESOLUTIONS = 'resolutions';
    /**#@-*/

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     *
     * @return \Amasty\Rma\Api\Data\ReturnOrderItemInterface
     */
    public function setItem($item);

    /**
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     */
    public function getItem();

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|bool $productItem
     *
     * @return \Amasty\Rma\Api\Data\ReturnOrderItemInterface
     */
    public function setProductItem($productItem);

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface|bool
     */
    public function getProductItem();

    /**
     * @param double $qty
     *
     * @return \Amasty\Rma\Api\Data\ReturnOrderItemInterface
     */
    public function setAvailableQty($qty);

    /**
     * @return double
     */
    public function getAvailableQty();

    /**
     * @param double $qty
     *
     * @return \Amasty\Rma\Api\Data\ReturnOrderItemInterface
     */
    public function setPurchasedQty($qty);

    /**
     * @return double
     */
    public function getPurchasedQty();

    /**
     * @param bool $isReturnable
     *
     * @return \Amasty\Rma\Api\Data\ReturnOrderItemInterface
     */
    public function setIsReturnable($isReturnable);
    /**
     * @return bool
     */
    public function isReturnable();

    /**
     * @param int $reason
     *
     * @return \Amasty\Rma\Api\Data\ReturnOrderItemInterface
     */
    public function setNoReturnableReason($reason);

    /**
     * @return int
     */
    public function getNoReturnableReason();

    /**
     * @param array $data
     *
     * @return \Amasty\Rma\Api\Data\ReturnOrderItemInterface
     */
    public function setNoReturnableData($data);

    /**
     * @return array
     */
    public function getNoReturnableData();

    /**
     * @param \Amasty\Rma\Api\Data\ResolutionInterface[] $resolutions
     *
     * @return \Amasty\Rma\Api\Data\ReturnOrderItemInterface
     */
    public function setResolutions($resolutions);

    /**
     * @return \Amasty\Rma\Api\Data\ResolutionInterface[]
     */
    public function getResolutions();
}
