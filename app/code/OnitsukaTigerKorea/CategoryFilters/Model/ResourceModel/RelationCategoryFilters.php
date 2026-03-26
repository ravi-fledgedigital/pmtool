<?php
namespace OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel;

class RelationCategoryFilters extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * This custruct use for category_filters_relation_row
     */
    protected function _construct()
    {
        $this->_init("category_filters_relation_row", "entity_id");
    }
}
