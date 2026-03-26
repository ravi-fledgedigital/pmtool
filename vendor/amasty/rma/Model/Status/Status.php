<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Status;

use Amasty\Rma\Api\Data\StatusInterface;
use Magento\Framework\Model\AbstractModel;

class Status extends AbstractModel implements StatusInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Status\ResourceModel\Status::class);
        $this->setIdFieldName(StatusInterface::STATUS_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStatusId($statusId)
    {
        return $this->setData(StatusInterface::STATUS_ID, (int)$statusId);
    }

    /**
     * @inheritdoc
     */
    public function getStatusId()
    {
        return (int)$this->_getData(StatusInterface::STATUS_ID);
    }

    /**
     * @inheritdoc
     */
    public function setIsEnabled($isEnabled)
    {
        return $this->setData(StatusInterface::IS_ENABLED, (bool)$isEnabled);
    }

    /**
     * @inheritdoc
     */
    public function isEnabled()
    {
        return (bool)$this->_getData(StatusInterface::IS_ENABLED);
    }

    /**
     * @inheritdoc
     */
    public function setIsInitial($isInitial)
    {
        return $this->setData(StatusInterface::IS_INITIAL, (bool)$isInitial);
    }

    /**
     * @inheritdoc
     */
    public function isInitial()
    {
        return (bool)$this->_getData(StatusInterface::IS_INITIAL);
    }

    /**
     * @inheritDoc
     */
    public function setAutoEvent($autoEvent)
    {
        return $this->setData(StatusInterface::AUTO_EVENT, (int)$autoEvent);
    }

    /**
     * @inheritDoc
     */
    public function getAutoEvent()
    {
        return (int)$this->_getData(StatusInterface::AUTO_EVENT);
    }

    /**
     * @inheritdoc
     */
    public function setState($state)
    {
        return $this->setData(StatusInterface::STATE, (int)$state);
    }

    /**
     * @inheritdoc
     */
    public function getState()
    {
        return (int)$this->_getData(StatusInterface::STATE);
    }

    /**
     * @inheritdoc
     */
    public function setGrid($grid)
    {
        return $this->setData(StatusInterface::GRID, (int)$grid);
    }

    /**
     * @inheritdoc
     */
    public function getGrid()
    {
        return (int)$this->_getData(StatusInterface::GRID);
    }

    /**
     * @inheritDoc
     */
    public function setPriority($priority)
    {
        return $this->setData(StatusInterface::PRIORITY, (int)$priority);
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return (int)$this->_getData(StatusInterface::PRIORITY);
    }

    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        return $this->setData(StatusInterface::TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->_getData(StatusInterface::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setColor($color)
    {
        return $this->setData(StatusInterface::COLOR, $color);
    }

    /**
     * @inheritdoc
     */
    public function getColor()
    {
        $color = $this->_getData(StatusInterface::COLOR);
        if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $color)) {
            return $color;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(StatusInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(StatusInterface::LABEL);
    }

    /**
     * @inheritDoc
     */
    public function setStoreData($store)
    {
        return $this->setDAta(StatusInterface::STORE, $store);
    }

    /**
     * @inheritDoc
     */
    public function getStoreData()
    {
        return $this->_getData(StatusInterface::STORE);
    }

    /**
     * @inheritdoc
     */
    public function setIsDeleted($isDeleted)
    {
        return $this->setData(StatusInterface::IS_DELETED, $isDeleted);
    }

    /**
     * @inheritdoc
     */
    public function getIsDeleted()
    {
        return $this->_getData(StatusInterface::IS_DELETED);
    }
}
