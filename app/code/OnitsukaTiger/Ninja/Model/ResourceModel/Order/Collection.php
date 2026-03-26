<?php


namespace OnitsukaTiger\Ninja\Model\ResourceModel\Order;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('OnitsukaTiger\Ninja\Model\Order', 'OnitsukaTiger\Ninja\Model\ResourceModel\Order');
    }
}
