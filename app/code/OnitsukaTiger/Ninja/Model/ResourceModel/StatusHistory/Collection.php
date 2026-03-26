<?php


namespace OnitsukaTiger\Ninja\Model\ResourceModel\StatusHistory;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('OnitsukaTiger\Ninja\Model\StatusHistory', 'OnitsukaTiger\Ninja\Model\ResourceModel\StatusHistory');
    }
}
