<?php
declare(strict_types=1);

namespace OnitsukaTiger\CancelShipment\Api\Data;

/**
 * Represents the sales event that brings to appending reservations.
 *
 * @api
 */
interface SalesEventInterface extends \Magento\InventorySalesApi\Api\Data\SalesEventInterface
{
    /**#@+
     * Constants for event types
     */
    const EVENT_SHIPMENT_CANCELED = 'shipment_canceled';
    /**#@-*/
}
