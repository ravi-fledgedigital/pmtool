<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Resolution;

use Amasty\Rma\Api\Data\ResolutionInterface;
use Magento\Framework\Model\AbstractModel;

class Resolution extends AbstractModel implements ResolutionInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Resolution\ResourceModel\Resolution::class);
        $this->setIdFieldName(ResolutionInterface::RESOLUTION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setResolutionId($resolutionId)
    {
        return $this->setData(ResolutionInterface::RESOLUTION_ID, (int)$resolutionId);
    }

    /**
     * @inheritdoc
     */
    public function getResolutionId()
    {
        return (int)$this->_getData(ResolutionInterface::RESOLUTION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        return $this->setData(ResolutionInterface::TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->_getData(ResolutionInterface::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(ResolutionInterface::STATUS, (int)$status);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return (int)$this->_getData(ResolutionInterface::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setPosition($position)
    {
        return $this->setData(ResolutionInterface::POSITION, (int)$position);
    }

    /**
     * @inheritdoc
     */
    public function getPosition()
    {
        return (int)$this->_getData(ResolutionInterface::POSITION);
    }

    /**
     * @inheritdoc
     */
    public function setStores($stores)
    {
        return $this->setData(ResolutionInterface::STORES, $stores);
    }

    /**
     * @inheritdoc
     */
    public function getStores()
    {
        return $this->_getData(ResolutionInterface::STORES);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(ResolutionInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(ResolutionInterface::LABEL);
    }

    /**
     * @inheritdoc
     */
    public function setIsDeleted($isDeleted)
    {
        return $this->setData(ResolutionInterface::IS_DELETED, (bool)$isDeleted);
    }

    /**
     * @inheritdoc
     */
    public function getIsDeleted()
    {
        return (bool)$this->_getData(ResolutionInterface::IS_DELETED);
    }
}
