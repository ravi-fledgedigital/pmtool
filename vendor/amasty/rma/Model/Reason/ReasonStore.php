<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Reason;

use Amasty\Rma\Api\Data\ReasonStoreInterface;
use Magento\Framework\Model\AbstractModel;

class ReasonStore extends AbstractModel implements ReasonStoreInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Reason\ResourceModel\ReasonStore::class);
        $this->setIdFieldName(ReasonStoreInterface::REASON_STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setReasonStoreId($reasonStoreId)
    {
        return $this->setData(ReasonStoreInterface::REASON_STORE_ID, (int)$reasonStoreId);
    }

    /**
     * @inheritdoc
     */
    public function getReasonStoreId()
    {
        return (int) $this->_getData(ReasonStoreInterface::REASON_STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setReasonId($reasonId)
    {
        return $this->setData(ReasonStoreInterface::REASON_ID, (int)$reasonId);
    }

    /**
     * @inheritdoc
     */
    public function getReasonId()
    {
        return (int)$this->_getData(ReasonStoreInterface::REASON_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(ReasonStoreInterface::STORE_ID, (int)$storeId);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return (int)$this->_getData(ReasonStoreInterface::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(ReasonStoreInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(ReasonStoreInterface::LABEL);
    }
}
