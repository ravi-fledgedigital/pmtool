<?php
namespace OnitsukaTiger\PortOne\Model\ResourceModel\PortOne;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\PortOne\Model\PortOne::class,
            \OnitsukaTiger\PortOne\Model\ResourceModel\PortOne::class
        );
    }
}
