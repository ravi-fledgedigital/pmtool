<?php

namespace OnitsukaTigerCpss\PaymentList\Model;

class PaymentMethod extends \Magento\Framework\Model\AbstractModel
{
    const CACHE_TAG = 'crm_real_store_payment_list';

    protected $_cacheTag = 'crm_real_store_payment_list';

    protected $_eventPrefix = 'crm_real_store_payment_list';

    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(\OnitsukaTigerCpss\PaymentList\Model\ResourceModel\PaymentMethod::class);
    }
}
