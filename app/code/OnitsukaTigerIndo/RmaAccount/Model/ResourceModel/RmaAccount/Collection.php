<?php

namespace OnitsukaTigerIndo\RmaAccount\Model\ResourceModel\RmaAccount;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'rma_id';

    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTigerIndo\RmaAccount\Model\RmaAccount::class,
            \OnitsukaTigerIndo\RmaAccount\Model\ResourceModel\RmaAccount::class
        );
    }
}
