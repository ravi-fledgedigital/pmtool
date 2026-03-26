<?php

namespace Seoulwebdesign\Toast\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Seoulwebdesign\Toast\Helper\Data;

class SendOrderMessage implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * SendOrderMessage constructor.
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();
        if ($this->_helper->getIsEnabled($order->getStoreId())) {
            if ($this->_helper->addLog($order->getId(), $order->getStatus())) {
                $data['order']= $order;
                $shipData = $this->_helper->getOrderTracking($order);
                $data['tracking'] = $shipData['track_numbers'];
                $data['courier'] = $shipData['carrier_titles'];
                $data['courier_code'] = $shipData['carrier_codes'];
                $data['storeId'] = $order->getStoreId();
                $this->_helper->sendMessage($order->getStatus(), $data, true);
            }
        }
    }
}
