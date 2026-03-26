<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Condition;

use Amasty\Rma\Api\Data\ConditionStoreInterface;
use Magento\Framework\Model\AbstractModel;

class ConditionStore extends AbstractModel implements ConditionStoreInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Condition\ResourceModel\ConditionStore::class);
        $this->setIdFieldName(ConditionStoreInterface::CONDITION_STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setConditionStoreId($conditionStoreId)
    {
        return $this->setData(ConditionStoreInterface::CONDITION_STORE_ID, (int)$conditionStoreId);
    }

    /**
     * @inheritdoc
     */
    public function getConditionStoreId()
    {
        return (int) $this->_getData(ConditionStoreInterface::CONDITION_STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setConditionId($conditionId)
    {
        return $this->setData(ConditionStoreInterface::CONDITION_ID, (int)$conditionId);
    }

    /**
     * @inheritdoc
     */
    public function getConditionId()
    {
        return (int)$this->_getData(ConditionStoreInterface::CONDITION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(ConditionStoreInterface::STORE_ID, (int)$storeId);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return (int)$this->_getData(ConditionStoreInterface::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(ConditionStoreInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(ConditionStoreInterface::LABEL);
    }
}
