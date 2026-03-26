<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request;

use Amasty\Rma\Api\Data\TrackingInterface;
use Magento\Framework\Model\AbstractModel;

class Tracking extends AbstractModel implements TrackingInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Request\ResourceModel\Tracking::class);
        $this->setIdFieldName(TrackingInterface::TRACKING_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTrackingId($trackingId)
    {
        return $this->setData(TrackingInterface::TRACKING_ID, (int)$trackingId);
    }

    /**
     * @inheritdoc
     */
    public function getTrackingId()
    {
        return (int)$this->_getData(TrackingInterface::TRACKING_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRequestId($requestId)
    {
        return $this->setData(TrackingInterface::REQUEST_ID, (int)$requestId);
    }

    /**
     * @inheritdoc
     */
    public function getRequestId()
    {
        return (int)$this->_getData(TrackingInterface::REQUEST_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTrackingCode($trackingCode)
    {
        return $this->setData(TrackingInterface::TRACKING_CODE, $trackingCode);
    }

    /**
     * @inheritdoc
     */
    public function getTrackingCode()
    {
        return $this->_getData(TrackingInterface::TRACKING_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setTrackingNumber($trackingNumber)
    {
        return $this->setData(TrackingInterface::TRACKING_NUMBER, $trackingNumber);
    }

    /**
     * @inheritdoc
     */
    public function getTrackingNumber()
    {
        return $this->_getData(TrackingInterface::TRACKING_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setIsCustomer($isCustomer)
    {
        return $this->setData(TrackingInterface::IS_CUSTOMER, (bool)$isCustomer);
    }

    /**
     * @inheritDoc
     */
    public function isCustomer()
    {
        return (bool)$this->_getData(TrackingInterface::IS_CUSTOMER);
    }
}
