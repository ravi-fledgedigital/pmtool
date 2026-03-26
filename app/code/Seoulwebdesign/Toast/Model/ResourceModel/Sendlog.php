<?php

namespace Seoulwebdesign\Toast\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Sendlog extends AbstractDb
{
    public const TABLE_NAME = 'seoulwebdesign_toast_sendlog';

    /**
     * The contructor
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
        $this->_init(self::TABLE_NAME, 'id');
    }
}
