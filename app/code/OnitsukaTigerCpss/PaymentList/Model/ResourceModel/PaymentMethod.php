<?php

namespace OnitsukaTigerCpss\PaymentList\Model\ResourceModel;

class PaymentMethod extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('crm_real_store_payment_list','payment_id');
    }
}
