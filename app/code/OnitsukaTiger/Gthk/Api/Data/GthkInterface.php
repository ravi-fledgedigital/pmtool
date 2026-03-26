<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Gthk\Api\Data;

interface GthkInterface
{

    const ORDER_ID = 'Order_id';
    const GTHK_ID = 'gthk_id';
    const CREATED_AT = 'created_at';
    const JSON_DATA = 'json_data';
    const SHIPMENT_ID = 'shipment_id';
    const TRACKING_ID = 'tracking_id';

    /**
     * Get gthk_id
     * @return string|null
     */
    public function getGthkId();

    /**
     * Set gthk_id
     * @param string $gthkId
     * @return \OnitsukaTiger\Gthk\Gthk\Api\Data\GthkInterface
     */
    public function setGthkId($gthkId);

    /**
     * Get Order_id
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set Order_id
     * @param string $orderId
     * @return \OnitsukaTiger\Gthk\Gthk\Api\Data\GthkInterface
     */
    public function setOrderId($orderId);

    /**
     * Get shipment_id
     * @return string|null
     */
    public function getShipmentId();

    /**
     * Set shipment_id
     * @param string $shipmentId
     * @return \OnitsukaTiger\Gthk\Gthk\Api\Data\GthkInterface
     */
    public function setShipmentId($shipmentId);

    /**
     * Get tracking_id
     * @return string|null
     */
    public function getTrackingId();

    /**
     * Set tracking_id
     * @param string $trackingId
     * @return \OnitsukaTiger\Gthk\Gthk\Api\Data\GthkInterface
     */
    public function setTrackingId($trackingId);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \OnitsukaTiger\Gthk\Gthk\Api\Data\GthkInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get json_data
     * @return string|null
     */
    public function getJsonData();

    /**
     * Set json_data
     * @param string $jsonData
     * @return \OnitsukaTiger\Gthk\Gthk\Api\Data\GthkInterface
     */
    public function setJsonData($jsonData);
}

