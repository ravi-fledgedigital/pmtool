<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetSuite\Model\EC;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use OnitsukaTiger\NetSuite\Api\Queue\CancelMessageInterface;

class CancelShipmentQueue {

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var CancelMessageInterface
     */
    protected $message;

    /**
     * CancelShipmentQueue constructor.
     * @param PublisherInterface $publisher
     * @param CancelMessageInterface $message
     */
    public function __construct(
        PublisherInterface $publisher,
        CancelMessageInterface $message
    ){
        $this->publisher = $publisher;
        $this->message = $message;
    }

    /**
     * @param ShipmentInterface $shipment
     */
    public function toQueue(ShipmentInterface $shipment){
        // Pass to Messsage Queue
        $this->message->setShipmentId($shipment->getIncrementId());
        $this->message->setStoreId($shipment->getStoreId());
        $this->message->setSourceCode($shipment->getExtensionAttributes()->getSourceCode());
        $this->message->setRetry(0);
        $this->publisher->publish(
            CancelMessageInterface::TOPIC_NAME,
            $this->message
        );
    }
}
