<?php

namespace OnitsukaTiger\Restock\Model\ResourceModel\Grid;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'alert_stock_id';
    /**
     * Define resource model.
     */
    protected function _construct()
    {
        $this->_init(\OnitsukaTiger\Restock\Model\Grid::class, \OnitsukaTiger\Restock\Model\ResourceModel\Grid::class);
    }
}
