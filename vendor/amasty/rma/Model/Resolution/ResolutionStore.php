<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Resolution;

use Amasty\Rma\Api\Data\ResolutionStoreInterface;
use Magento\Framework\Model\AbstractModel;

class ResolutionStore extends AbstractModel implements ResolutionStoreInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Resolution\ResourceModel\ResolutionStore::class);
        $this->setIdFieldName(ResolutionStoreInterface::RESOLUTION_STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setResolutionStoreId($resolutionStoreId)
    {
        return $this->setData(ResolutionStoreInterface::RESOLUTION_STORE_ID, (int)$resolutionStoreId);
    }

    /**
     * @inheritdoc
     */
    public function getResolutionStoreId()
    {
        return (int) $this->_getData(ResolutionStoreInterface::RESOLUTION_STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setResolutionId($resolutionId)
    {
        return $this->setData(ResolutionStoreInterface::RESOLUTION_ID, (int)$resolutionId);
    }

    /**
     * @inheritdoc
     */
    public function getResolutionId()
    {
        return (int)$this->_getData(ResolutionStoreInterface::RESOLUTION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(ResolutionStoreInterface::STORE_ID, (int)$storeId);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return (int)$this->_getData(ResolutionStoreInterface::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(ResolutionStoreInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(ResolutionStoreInterface::LABEL);
    }
}
