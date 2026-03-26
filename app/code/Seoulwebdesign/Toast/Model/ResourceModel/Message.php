<?php

namespace Seoulwebdesign\Toast\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Message extends AbstractDb
{
    public const TABLE_NAME = 'seoulwebdesign_toast_template';

    /**
     * The constructor
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * The initial
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'message_id');
    }
}
