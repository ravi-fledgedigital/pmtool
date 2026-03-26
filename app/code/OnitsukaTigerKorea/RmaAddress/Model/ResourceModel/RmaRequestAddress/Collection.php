<?php

namespace OnitsukaTigerKorea\RmaAddress\Model\ResourceModel\RmaRequestAddress;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTigerKorea\RmaAddress\Model\RmaRequestAddress::class,
            \OnitsukaTigerKorea\RmaAddress\Model\ResourceModel\RmaRequestAddress::class
        );
    }
}
