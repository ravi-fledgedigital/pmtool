<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Order;

use Amasty\Rma\Api\Data\ReturnOrderItemInterface;
use Magento\Framework\DataObject;

class ReturnOrderItem extends DataObject implements ReturnOrderItemInterface
{

    /**
     * @inheritdoc
     */
    public function setItem($item)
    {
        return $this->setData(ReturnOrderItemInterface::ITEM, $item);
    }

    /**
     * @inheritdoc
     */
    public function getItem()
    {
        return $this->_getData(ReturnOrderItemInterface::ITEM);
    }

    /**
     * @inheritdoc
     */
    public function setProductItem($productItem)
    {
        return $this->setData(ReturnOrderItemInterface::PRODUCT_ITEM, $productItem);
    }

    /**
     * @inheritdoc
     */
    public function getProductItem()
    {
        return $this->_getData(ReturnOrderItemInterface::PRODUCT_ITEM);
    }

    /**
     * @inheritdoc
     */
    public function setAvailableQty($qty)
    {
        return $this->setData(ReturnOrderItemInterface::AVAILABLE_QTY, (double)$qty);
    }

    /**
     * @inheritdoc
     */
    public function getAvailableQty()
    {
        return (double)$this->_getData(ReturnOrderItemInterface::AVAILABLE_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setPurchasedQty($qty)
    {
        return $this->setData(ReturnOrderItemInterface::PURCHASED_QTY, (double)$qty);
    }

    /**
     * @inheritdoc
     */
    public function getPurchasedQty()
    {
        return (double)$this->_getData(ReturnOrderItemInterface::PURCHASED_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setIsReturnable($isReturnable)
    {
        return $this->setData(ReturnOrderItemInterface::IS_RETURNABLE, (bool)$isReturnable);
    }

    /**
     * @inheritdoc
     */
    public function isReturnable()
    {
        return (bool)$this->_getData(ReturnOrderItemInterface::IS_RETURNABLE);
    }

    /**
     * @inheritdoc
     */
    public function setNoReturnableReason($reason)
    {
        return $this->setData(ReturnOrderItemInterface::NO_RETURNABLE_REASON, (int)$reason);
    }

    /**
     * @inheritdoc
     */
    public function getNoReturnableReason()
    {
        return (int)$this->_getData(ReturnOrderItemInterface::NO_RETURNABLE_REASON);
    }

    /**
     * @inheritdoc
     */
    public function setNoReturnableData($data)
    {
        return $this->setData(ReturnOrderItemInterface::NO_RETURNABLE_DATA, $data);
    }

    /**
     * @inheritdoc
     */
    public function getNoReturnableData()
    {
        if (empty($this->_getData(ReturnOrderItemInterface::NO_RETURNABLE_DATA))) {
            return [];
        }

        return $this->_getData(ReturnOrderItemInterface::NO_RETURNABLE_DATA);
    }

    /**
     * @inheritdoc
     */
    public function setResolutions($resolutions)
    {
        return $this->setData(ReturnOrderItemInterface::RESOLUTIONS, $resolutions);
    }

    /**
     * @inheritdoc
     */
    public function getResolutions()
    {
        if (empty($this->_getData(ReturnOrderItemInterface::RESOLUTIONS))) {
            return [];
        }

        return $this->getData(ReturnOrderItemInterface::RESOLUTIONS);
    }
}
