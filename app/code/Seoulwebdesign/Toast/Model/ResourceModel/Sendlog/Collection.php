<?php

namespace Seoulwebdesign\Toast\Model\ResourceModel\Sendlog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Seoulwebdesign\Toast\Model\Sendlog;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';
    /**
     * @var string
     */
    protected $_eventPrefix = 'seoulwebdesign_toast_sendlog_collection';
    /**
     * @var string
     */
    protected $_eventObject = 'swd_toast_sendlog';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Sendlog::class,
            \Seoulwebdesign\Toast\Model\ResourceModel\Sendlog::class
        );
    }
}
