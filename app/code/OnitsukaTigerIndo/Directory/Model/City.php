<?php

namespace OnitsukaTigerIndo\Directory\Model;

/**
 * Class City
 * @package OnitsukaTigerIndo\Directory\Model
 */
class City extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'directory_country_cities';

    protected $_cacheTag = 'directory_country_cities';

    protected $_eventPrefix = 'directory_country_cities';

    protected function _construct()
    {
        $this->_init('OnitsukaTigerIndo\Directory\Model\ResourceModel\City');
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
