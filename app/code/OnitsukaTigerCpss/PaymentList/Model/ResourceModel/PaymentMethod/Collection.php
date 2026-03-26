<?php

namespace OnitsukaTigerCpss\PaymentList\Model\ResourceModel\PaymentMethod;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     *
     * @var type
     */
    protected $_idFieldName = 'payment_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\OnitsukaTigerCpss\PaymentList\Model\PaymentMethod::class,
            \OnitsukaTigerCpss\PaymentList\Model\ResourceModel\PaymentMethod::class);
        $this->_map['fields']['payment_id'] = 'main_table.payment_id';
    }
}
