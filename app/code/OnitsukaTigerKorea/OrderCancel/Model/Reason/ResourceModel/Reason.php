<?php

namespace OnitsukaTigerKorea\OrderCancel\Model\Reason\ResourceModel;

use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Reason extends AbstractDb
{
    public const TABLE_NAME = 'order_cancel_reason';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, ReasonInterface::REASON_ID);
    }
}
