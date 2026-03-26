<?php

namespace Cpss\Crm\Model\ResourceModel\ShopReceipt;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'sales_real_store_order';
    protected $_eventObject = 'sales_real_store_order_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Cpss\Crm\Model\ShopReceipt', 'Cpss\Crm\Model\ResourceModel\ShopReceipt');
    }
}
