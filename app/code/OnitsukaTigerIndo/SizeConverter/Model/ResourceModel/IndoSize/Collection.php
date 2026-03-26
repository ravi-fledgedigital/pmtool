<?php

namespace OnitsukaTigerIndo\SizeConverter\Model\ResourceModel\IndoSize;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     *
     * @var type
     */
    protected $_idFieldName = 'size_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\OnitsukaTigerIndo\SizeConverter\Model\IndoSize::class, \OnitsukaTigerIndo\SizeConverter\Model\ResourceModel\IndoSize::class);
        $this->_map['fields']['size_id'] = 'main_table.size_id';
    }
}
