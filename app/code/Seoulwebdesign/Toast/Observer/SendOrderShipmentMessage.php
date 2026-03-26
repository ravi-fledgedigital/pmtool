<?php

namespace Seoulwebdesign\Toast\Observer;

use Magento\Framework\Event\Observer;
use Seoulwebdesign\Toast\Helper\Data;

class SendOrderShipmentMessage implements \Magento\Framework\Event\ObserverInterface
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
        /** @var $shipment \Magento\Sales\Model\Order\Shipment */
        $shipment = $observer->getEvent()->getShipment();
        /* @var $order \Magento\Sales\Model\Order */
        $order = $shipment->getOrder();
        if ($this->_helper->getIsEnabled($order->getStoreId())) {
            $tracks = $shipment->getAllTracks();
            $trackData = [];
            foreach ($tracks as $track) {
                $data = $track->getData();
                if (isset($data['carrier_code']) && isset($data['track_number'])) {
                    //$trackData[] = $data['carrier_code'] . "-" . $data['track_number'];
                    //only show tracking number sd
                    $trackData[] = $data['track_number'];
                }
            }
            $this->_helper->sendMessage(\Seoulwebdesign\Toast\Model\Message::ORDER_DELIVERING, [
                'tracking' => implode(',', $trackData),
                'order' => $order
            ], true);
        }
    }
}
