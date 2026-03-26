<?php

namespace OnitsukaTiger\OrderEmails\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\Sales\Helper\Data;

class SendEmailsShipped implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var Data
     */
    protected $mailSender;

    /**
     * @var ShipmentSender
     */
    protected $shipmentSender;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * SendEmailsShipped constructor.
     * @param Data $mailSender
     * @param ShipmentSender $shipmentSender
     * @param Session $customerSession
     */
    public function __construct(
        Data $mailSender,
        ShipmentSender $shipmentSender,
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
        $this->mailSender = $mailSender;
        $this->shipmentSender = $shipmentSender;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        $this->shipmentSender->send($shipment);
    }
}
