<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Gthk\Model;

use Magento\Framework\Model\AbstractModel;
use OnitsukaTiger\Gthk\Api\Data\GthkInterface;

class Gthk extends AbstractModel implements GthkInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\OnitsukaTiger\Gthk\Model\ResourceModel\Gthk::class);
    }

    /**
     * @inheritDoc
     */
    public function getGthkId()
    {
        return $this->getData(self::GTHK_ID);
    }

    /**
     * @inheritDoc
     */
    public function setGthkId($gthkId)
    {
        return $this->setData(self::GTHK_ID, $gthkId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getShipmentId()
    {
        return $this->getData(self::SHIPMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setShipmentId($shipmentId)
    {
        return $this->setData(self::SHIPMENT_ID, $shipmentId);
    }

    /**
     * @inheritDoc
     */
    public function getTrackingId()
    {
        return $this->getData(self::TRACKING_ID);
    }

    /**
     * @inheritDoc
     */
    public function setTrackingId($trackingId)
    {
        return $this->setData(self::TRACKING_ID, $trackingId);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getJsonData()
    {
        return $this->getData(self::JSON_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setJsonData($jsonData)
    {
        return $this->setData(self::JSON_DATA, $jsonData);
    }
}

