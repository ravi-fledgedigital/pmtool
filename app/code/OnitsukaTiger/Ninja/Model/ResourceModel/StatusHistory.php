<?php


namespace OnitsukaTiger\Ninja\Model\ResourceModel;


class StatusHistory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const MAIN_TABLE = 'ninja_status_history';
    const ID_FIELD_NAME = 'status_history_id';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
