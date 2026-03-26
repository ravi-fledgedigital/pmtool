<?php

namespace OnitsukaTigerIndo\Directory\Model;

/**
 * Class District
 * @package OnitsukaTigerIndo\Directory\Model
 */
class District extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'directory_country_district';

    protected $_cacheTag = 'directory_country_district';

    protected $_eventPrefix = 'directory_country_district';

    protected function _construct()
    {
        $this->_init('OnitsukaTigerIndo\Directory\Model\ResourceModel\District');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
