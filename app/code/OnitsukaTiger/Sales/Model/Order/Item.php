<?php
declare(strict_types=1);

namespace OnitsukaTiger\Sales\Model\Order;

class Item extends \Magento\Sales\Model\Order\Item {

    /**
     * Retrieve item qty available for ship
     *
     * @return float|integer
     */
    public function getSimpleQtyToShip()
    {
        $qty = $this->getQtyOrdered() - $this->getQtyShipped() - $this->getQtyRefunded() - $this->getQtyCanceled();
        return max(round($qty, 8), 0);
    }
}
