<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Reason;

use Amasty\Rma\Api\Data\ReasonInterface;
use Magento\Framework\Model\AbstractModel;

class Reason extends AbstractModel implements ReasonInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Reason\ResourceModel\Reason::class);
        $this->setIdFieldName(ReasonInterface::REASON_ID);
    }

    /**
     * @inheritdoc
     */
    public function setReasonId($reasonId)
    {
        return $this->setData(ReasonInterface::REASON_ID, (int)$reasonId);
    }

    /**
     * @inheritdoc
     */
    public function getReasonId()
    {
        return (int)$this->_getData(ReasonInterface::REASON_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        return $this->setData(ReasonInterface::TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->_getData(ReasonInterface::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(ReasonInterface::STATUS, (int)$status);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return (int)$this->_getData(ReasonInterface::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setPosition($position)
    {
        return $this->setData(ReasonInterface::POSITION, (int)$position);
    }

    /**
     * @inheritdoc
     */
    public function getPosition()
    {
        return (int)$this->_getData(ReasonInterface::POSITION);
    }

    /**
     * @inheritdoc
     */
    public function setStores($stores)
    {
        return $this->setData(ReasonInterface::STORES, $stores);
    }

    /**
     * @inheritdoc
     */
    public function getStores()
    {
        return $this->_getData(ReasonInterface::STORES);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(ReasonInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(ReasonInterface::LABEL);
    }

    /**
     * @inheritdoc
     */
    public function setPayer($payer)
    {
        return $this->setData(ReasonInterface::PAYER, (int)$payer);
    }

    /**
     * @inheritdoc
     */
    public function getPayer()
    {
        return $this->_getData(ReasonInterface::PAYER);
    }

    /**
     * @inheritdoc
     */
    public function setIsDeleted($isDeleted)
    {
        return $this->setData(ReasonInterface::IS_DELETED, $isDeleted);
    }

    /**
     * @inheritdoc
     */
    public function getIsDeleted()
    {
        return $this->_getData(ReasonInterface::IS_DELETED);
    }
}
