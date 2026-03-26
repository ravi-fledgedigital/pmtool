<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Condition;

use Amasty\Rma\Api\Data\ConditionInterface;
use Magento\Framework\Model\AbstractModel;

class Condition extends AbstractModel implements ConditionInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Condition\ResourceModel\Condition::class);
        $this->setIdFieldName(ConditionInterface::CONDITION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setConditionId($conditionId)
    {
        return $this->setData(ConditionInterface::CONDITION_ID, (int)$conditionId);
    }

    /**
     * @inheritdoc
     */
    public function getConditionId()
    {
        return (int)$this->_getData(ConditionInterface::CONDITION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        return $this->setData(ConditionInterface::TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->_getData(ConditionInterface::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(ConditionInterface::STATUS, (int)$status);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return (int)$this->_getData(ConditionInterface::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setPosition($position)
    {
        return $this->setData(ConditionInterface::POSITION, (int)$position);
    }

    /**
     * @inheritdoc
     */
    public function getPosition()
    {
        return (int)$this->_getData(ConditionInterface::POSITION);
    }

    /**
     * @inheritdoc
     */
    public function setStores($stores)
    {
        return $this->setData(ConditionInterface::STORES, $stores);
    }

    /**
     * @inheritdoc
     */
    public function getStores()
    {
        return $this->_getData(ConditionInterface::STORES);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(ConditionInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(ConditionInterface::LABEL);
    }

    /**
     * @inheritdoc
     */
    public function setIsDeleted($isDeleted)
    {
        return $this->setData(ConditionInterface::IS_DELETED, $isDeleted);
    }

    /**
     * @inheritdoc
     */
    public function getIsDeleted()
    {
        return $this->_getData(ConditionInterface::IS_DELETED);
    }
}
