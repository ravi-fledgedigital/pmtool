<?php

namespace OnitsukaTigerIndo\Directory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class City
 * @package OnitsukaTigerIndo\Directory\Model\ResourceModel
 */
class City extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('directory_country_cities', 'entity_id');
    }
}
