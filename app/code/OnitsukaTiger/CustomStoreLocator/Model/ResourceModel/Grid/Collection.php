<?php

namespace OnitsukaTiger\CustomStoreLocator\Model\ResourceModel\Grid;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\CustomStoreLocator\Model\Grid::class,
            \OnitsukaTiger\CustomStoreLocator\Model\ResourceModel\Grid::class
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->order('position ASC'); // Sort stores by position
    }
}
