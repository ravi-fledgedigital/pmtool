<?php

namespace OnitsukaTigerKorea\OrderCancel\Model\Reason\ResourceModel;

use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonStoreInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ReasonStore extends AbstractDb
{
    public const TABLE_NAME = 'order_cancel_reason_store';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, ReasonStoreInterface::REASON_STORE_ID);
    }
}
