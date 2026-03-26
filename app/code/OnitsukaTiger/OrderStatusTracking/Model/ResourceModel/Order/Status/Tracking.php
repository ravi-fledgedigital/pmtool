<?php
namespace OnitsukaTiger\OrderStatusTracking\Model\ResourceModel\Order\Status;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Tracking extends AbstractDb
{
    protected function _construct() {
        $this->_init('sales_order_status_tracking', 'entity_id');
    }
}
