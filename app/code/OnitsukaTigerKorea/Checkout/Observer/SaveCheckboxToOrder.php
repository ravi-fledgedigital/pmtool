<?php

namespace OnitsukaTigerKorea\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class SaveCheckboxToOrder implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        if ($quote->getData('use_personal_information')) {
            $order->setData('use_personal_information', $quote->getData('use_personal_information'));
        }
        if($quote->getGiftPackaging()) {
            $order->setData("gift_packaging", $quote->getGiftPackaging());
        }
    }
}
