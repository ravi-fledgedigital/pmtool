<?php

namespace OnitsukaTiger\OrderEmails\Observer;

use Magento\Framework\Event\Observer;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use \OnitsukaTiger\Sales\Helper\Data;
use \Magento\Customer\Model\Session;

class SendEmailsDelivered implements \Magento\Framework\Event\ObserverInterface{

    /**
     * @var Data
     */
    protected $mailSender;

    /**
     * @var Session
     */
    protected $customerSession;

    public function __construct(
        Data $mailSender,
        Session $customerSession
    )
    {
        $this->customerSession = $customerSession;
        $this->mailSender = $mailSender;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        $this->mailSender->sendDeliveredEmailTemplate($shipment);
    }
}
