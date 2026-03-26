<?php

namespace OnitsukaTiger\RestockReports\Model\ResourceModel;

class RestockReport extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('restock_queue', 'queue_id');
    }
}
