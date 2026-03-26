<?php
namespace Cpss\Crm\Model\ResourceModel\RealStore;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'crm_real_stores_collection';
	protected $_eventObject = 'realstores_collection';

    protected function _construct()
    {
        $this->_init('Cpss\Crm\Model\RealStore', 'Cpss\Crm\Model\ResourceModel\RealStore');
    }
}