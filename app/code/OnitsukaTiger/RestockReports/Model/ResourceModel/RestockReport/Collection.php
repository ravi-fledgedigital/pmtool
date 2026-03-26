<?php

namespace OnitsukaTiger\RestockReports\Model\ResourceModel\RestockReport;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     *
     * @var type
     */
    protected $_idFieldName = 'queue_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\RestockReports\Model\RestockReport::class,
            \OnitsukaTiger\RestockReports\Model\ResourceModel\RestockReport::class
        );
        $this->_map['fields']['queue_id'] = 'main_table.queue_id';
    }
}
