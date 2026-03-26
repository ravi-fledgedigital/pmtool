<?php
/**
 * Clickend Kerry module
 * @package Clickend\Kerry
 */
declare(strict_types=1);

namespace Clickend\Kerry\Model\ResourceModel;

class TrackingList extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('kerry_shipping_track', 'track_id');
    }
}
