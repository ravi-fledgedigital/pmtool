<?php
namespace OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel\CategoryFilters;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var  _idFieldName
     */
    protected $_idFieldName = "filter_id";

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            "OnitsukaTigerKorea\CategoryFilters\Model\CategoryFilters",
            "OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel\CategoryFilters"
        );
        $this->_map["fields"]["filter_id"] = "main_table.filter_id";
    }
}
