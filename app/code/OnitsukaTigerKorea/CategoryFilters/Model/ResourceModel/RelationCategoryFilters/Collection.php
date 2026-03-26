<?php
namespace OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel\RelationCategoryFilters;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var  _idFieldName
     */
    protected $_idFieldName = "entity_id";

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            "OnitsukaTigerKorea\CategoryFilters\Model\RelationCategoryFilters",
            "OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel\RelationCategoryFilters"
        );
        $this->_map["fields"]["entity_id"] = "main_table.entity_id";
    }
}
