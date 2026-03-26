<?php

namespace Seoulwebdesign\Toast\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Seoulwebdesign\Toast\Helper\Data;

class SendOrderPlaceMessage implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * The constructor
     *
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->_helper = $helper;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($this->_helper->getIsEnabled($order->getStoreId())) {
            /* @var $order \Magento\Sales\Model\Order */
            $this->_helper->sendMessage(\Seoulwebdesign\Toast\Model\Message::ORDER_PLACED, [
                'order' => $order,'storeId' => $order->getStoreId()
            ], true);
        }
    }
}
