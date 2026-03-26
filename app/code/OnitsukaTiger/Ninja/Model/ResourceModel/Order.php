<?php


namespace OnitsukaTiger\Ninja\Model\ResourceModel;


class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const MAIN_TABLE = 'ninja_order';
    const ID_FIELD_NAME = 'order_id';
    const TRACKING_NUMBER_FIELD_NAME = 'tracking_number';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }

    public function loadByTrackingNumber($model, $trackingNumber)
    {
        return $this->load($model, $trackingNumber, self::TRACKING_NUMBER_FIELD_NAME);
    }
}
