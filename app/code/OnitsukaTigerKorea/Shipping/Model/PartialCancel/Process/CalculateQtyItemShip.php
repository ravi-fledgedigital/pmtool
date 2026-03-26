<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Shipping\Model\PartialCancel\Process;

class CalculateQtyItemShip {

    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data){
        $itemQty = $data['items'] ?? [];
        foreach ($itemQty as $itemId => $quantity) {
            $data['items'][$itemId] = $quantity - $data['cancel_items'][$itemId] ;
        }
        return $data;
    }
}
