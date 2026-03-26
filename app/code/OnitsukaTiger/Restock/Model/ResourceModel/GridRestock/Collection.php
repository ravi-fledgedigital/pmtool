<?php
namespace OnitsukaTiger\Restock\Model\ResourceModel\GridRestock;

class Collection extends
    \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = "alert_stock_id";

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\OnitsukaTiger\Restock\Model\GridRestock::class, \OnitsukaTiger\Restock\Model\ResourceModel\GridRestock::class);
        $this->_map["fields"]["alert_stock_id"] = "main_table.alert_stock_id";
    }
}
