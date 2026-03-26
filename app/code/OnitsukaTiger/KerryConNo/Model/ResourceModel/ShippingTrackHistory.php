<?php


namespace OnitsukaTiger\KerryConNo\Model\ResourceModel;


class ShippingTrackHistory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const MAIN_TABLE = 'kerry_shipping_track_history';
    const ID_FIELD_NAME = 'history_track_id';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
