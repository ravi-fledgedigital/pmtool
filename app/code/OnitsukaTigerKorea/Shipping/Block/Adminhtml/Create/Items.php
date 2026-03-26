<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Shipping\Block\Adminhtml\Create;

class Items extends \Magento\Shipping\Block\Adminhtml\Create\Items {

    public function getStoreId($_items)
    {
        $storeId = 0;
        foreach ($_items as $_item) {
            return $_item->getStoreId();
        }
        return $storeId;
    }
}
