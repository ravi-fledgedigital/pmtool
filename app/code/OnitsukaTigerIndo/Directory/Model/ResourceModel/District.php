<?php

namespace OnitsukaTigerIndo\Directory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class District
 * @package OnitsukaTigerIndo\Directory\Model\ResourceModel
 */
class District extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('directory_country_district', 'entity_id');
    }
}
