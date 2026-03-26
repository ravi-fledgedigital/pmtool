<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetSuite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use OnitsukaTiger\NetSuite\Model\EC\CancelShipmentQueue;

/**
 * Class AfterCancelShipment
 * @package OnitsukaTiger\Netsuite\Observer
 */
class AfterCancelShipment implements ObserverInterface{

    /**
     * @var CancelShipmentQueue
     */
    protected $cancelShipmentQueue;

    /**
     * AfterCancelShipment constructor.
     * @param CancelShipmentQueue $cancelShipmentQueue
     */
    public function __construct(
        CancelShipmentQueue $cancelShipmentQueue
    )
    {
        $this->cancelShipmentQueue = $cancelShipmentQueue;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var ShipmentInterface $shipment */
        $shipment = $observer->getEvent()->getData('shipment');
        $this->cancelShipmentQueue->toQueue($shipment);
    }
}
