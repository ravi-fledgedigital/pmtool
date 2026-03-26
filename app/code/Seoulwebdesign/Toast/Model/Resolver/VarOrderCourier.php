<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Seoulwebdesign\Toast\Model\Message;

class VarOrderCourier extends VarOrderTrackingCode
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
            return $data['courier'];
        } catch (\Throwable $t) {
            return null;
        }
    }
}
