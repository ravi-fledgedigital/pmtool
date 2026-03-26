<?php

namespace OnitsukaTigerKorea\RmaAddress\Model\ResourceModel;

class RmaRequestAddress extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('rma_request_address', 'id');
    }
}
