<?php
namespace OnitsukaTigerKorea\CategoryFilters\Model\ResourceModel;

class CategoryFilters extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * This custruct use for category_filters
     */
    protected function _construct()
    {
        $this->_init("category_filters", "filter_id");
    }
}
