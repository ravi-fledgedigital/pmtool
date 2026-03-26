<?php
namespace Cpss\Pos\Model\ResourceModel\PosData;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'cpss_pos_pos_data_collection';
    protected $_eventObject = 'pos_data_collection';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Cpss\Pos\Model\PosData', 'Cpss\Pos\Model\ResourceModel\PosData');
    }
}
