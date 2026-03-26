<?php

namespace Seoulwebdesign\Toast\Model\ResourceModel\Message;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Seoulwebdesign\Toast\Model\Message;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'message_id';
    /**
     * @var string
     */
    protected $_eventPrefix = 'seoulwebdesign_toast_message_collection';
    /**
     * @var string
     */
    protected $_eventObject = 'swd_toast_message_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Message::class,
            \Seoulwebdesign\Toast\Model\ResourceModel\Message::class
        );
    }
}
