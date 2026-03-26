<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Shipping\Model\PartialCancel;

class IsPartialCancel {

    const CANCEL_PARTIALLY = 1;
    const NO_CANCEL_ITEM = 0;
    const NUMBER_DELETE = 2;

    /**
     * @param $data
     * @return array
     */
    public function execute($data){
        $isPartiallyCancel = [
            'status' => false,
            'type' => self::NO_CANCEL_ITEM
        ];
        $itemQty = $data['items'] ?? [];
        if(!isset($data['cancel_items'])){
            return $isPartiallyCancel;
        }
        $itemCancelQty = $data['cancel_items'];
        $flag = [];
        foreach($itemCancelQty as $itemId => $qtyCancel) {
            if(0 < $qtyCancel) {
                $flag[] = self::CANCEL_PARTIALLY;
            } else {
                $flag [] = self::NO_CANCEL_ITEM;
            }
        }

        if (in_array(self::CANCEL_PARTIALLY, $flag)) {
            $isPartiallyCancel = [
                'status' => true,
                'type' => self::CANCEL_PARTIALLY
            ];
        }
        return $isPartiallyCancel;
    }
}
