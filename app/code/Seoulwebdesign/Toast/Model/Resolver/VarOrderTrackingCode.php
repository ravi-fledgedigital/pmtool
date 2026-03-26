<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Seoulwebdesign\Toast\Model\Message;

class VarOrderTrackingCode
{
    /**
     * Main execute
     *
     * @param Message $message
     * @param array $data
     * @return string|null
     */
    public function execute($message, $data)
    {
        try {
            $order = $data['order'];
            $shipData = $this->getOrderTracking($order);
            $data['tracking'] = $shipData['track_numbers'];
            $data['courier'] = $shipData['carrier_titles'];
            $data['courier_code'] = $shipData['carrier_codes'];
            $data['storeId'] = $order->getStoreId();
            return $data['tracking'];
        } catch (\Throwable $t) {
            return null;
        }
    }

    /**
     * Get order tracking
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function getOrderTracking($order)
    {
        $shipments = $order->getShipmentsCollection();
        $trackData = [];
        $carrierCode = [];
        $carrierTitle = [];
        $re['track_numbers'] = '';
        $re['carrier_codes'] = '';
        $re['carrier_titles'] = '';

        if ($shipments) {
            foreach ($shipments as $shipment) {
                $tracks = $shipment->getAllTracks();
                foreach ($tracks as $track) {
                    $data = $track->getData();
                    if (isset($data['track_number'])) {
                        $trackData[]=$data['track_number'];
                    }
                    if (isset($data['carrier_code'])) {
                        $carrierCode[]=$data['carrier_code'];
                    }
                    if (isset($data['carrier_title'])) {
                        $carrierTitle[]=$data['carrier_title'];
                    }
                }
            }
        }
        if ($trackData) {
            $re['track_numbers'] = implode(',', $trackData);
            $re['carrier_codes'] = implode(',', $carrierCode);
            $re['carrier_titles'] = implode(',', $carrierTitle);
            return $re;
        }
        return $re;
    }
}
