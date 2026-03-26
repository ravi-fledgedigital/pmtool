<?php

namespace OnitsukaTigerIndo\RmaAccount\Model\ResourceModel;

class RmaAccount extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('onitsukatigerindo_rmaaccount', 'rma_id');
    }
}
