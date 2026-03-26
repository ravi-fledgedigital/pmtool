<?php
namespace OnitsukaTiger\NetsuiteReturnOrderSync\Model;

class CleanShipmentItemSelections {

    /**
     * @param $shipmentItemSelection
     * @return mixed
     */
    public function execute($shipmentItemSelection)
    {
        foreach ($shipmentItemSelection as $key => $shipmentSelection) {
            if ($shipmentSelection['qtyToDeduct'] == 0) {
                unset($shipmentItemSelection[$key]);
            }
            if (count($shipmentSelection) == 0) {
                unset($shipmentSelection);
            }
        }
        return $shipmentItemSelection;
    }
}
