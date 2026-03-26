<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request;

use Amasty\Rma\Api\Data\RequestItemInterface;
use Magento\Framework\Model\AbstractModel;

class RequestItem extends AbstractModel implements RequestItemInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Request\ResourceModel\RequestItem::class);
        $this->setIdFieldName(RequestItemInterface::REQUEST_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRequestItemId($requestItemId)
    {
        return $this->setData(RequestItemInterface::REQUEST_ITEM_ID, (int)$requestItemId);
    }

    /**
     * @inheritdoc
     */
    public function getRequestItemId()
    {
        return (int)$this->_getData(RequestItemInterface::REQUEST_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRequestId($requestId)
    {
        return $this->setData(RequestItemInterface::REQUEST_ID, (int)$requestId);
    }

    /**
     * @inheritdoc
     */
    public function getRequestId()
    {
        return (int)$this->_getData(RequestItemInterface::REQUEST_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderItemId($orderItemId)
    {
        return $this->setData(RequestItemInterface::ORDER_ITEM_ID, (int)$orderItemId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderItemId()
    {
        return (int)$this->_getData(RequestItemInterface::ORDER_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setQty($qty)
    {
        return $this->setData(RequestItemInterface::QTY, (double)$qty);
    }

    /**
     * @inheritdoc
     */
    public function getQty()
    {
        return (double)$this->_getData(RequestItemInterface::QTY);
    }

    /**
     * @inheritDoc
     */
    public function setRequestQty($requestQty)
    {
        return $this->setData(RequestItemInterface::REQUEST_QTY, (double)$requestQty);
    }

    /**
     * @inheritDoc
     */
    public function getRequestQty()
    {
        return (double)$this->_getData(RequestItemInterface::REQUEST_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setReasonId($reasonId)
    {
        return $this->setData(RequestItemInterface::REASON_ID, (int)$reasonId);
    }

    /**
     * @inheritdoc
     */
    public function getReasonId()
    {
        return (int)$this->_getData(RequestItemInterface::REASON_ID);
    }

    /**
     * @inheritdoc
     */
    public function setConditionId($conditionId)
    {
        return $this->setData(RequestItemInterface::CONDITION_ID, (int)$conditionId);
    }

    /**
     * @inheritdoc
     */
    public function getConditionId()
    {
        return (int)$this->_getData(RequestItemInterface::CONDITION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setResolutionId($resolutionId)
    {
        return $this->setData(RequestItemInterface::RESOLUTION_ID, (int)$resolutionId);
    }

    /**
     * @inheritdoc
     */
    public function getResolutionId()
    {
        return (int)$this->_getData(RequestItemInterface::RESOLUTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setItemStatus($itemStatus)
    {
        return $this->setData(RequestItemInterface::ITEM_STATUS, (int)$itemStatus);
    }

    /**
     * @inheritDoc
     */
    public function getItemStatus()
    {
        return (int)$this->_getData(RequestItemInterface::ITEM_STATUS);
    }
}
