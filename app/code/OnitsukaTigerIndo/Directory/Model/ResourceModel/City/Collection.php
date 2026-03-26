<?php

namespace OnitsukaTigerIndo\Directory\Model\ResourceModel\City;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package OnitsukaTigerIndo\Directory\Model\ResourceModel\City
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected $_eventPrefix = 'directory_country_cities_collection';

    protected $_eventObject = 'country_cities_collection';

    protected function _construct()
    {
        $this->_init('OnitsukaTigerIndo\Directory\Model\City', 'OnitsukaTigerIndo\Directory\Model\ResourceModel\City');
    }

    /**
     * @return $this
     */
    public function getOptionsProvince()
    {
        $this->getSelect()->joinInner(
            ['regionTable' => $this->getTable('directory_country_region')],
            'regionTable.code = main_table.province_code',
            'regionTable.region_id as region_id'
        );

        return $this;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $cities = $this->getOptionsProvince()->getData();
        $data = [];
        foreach ($cities as $city) {
            $data[$city['region_id']][] = [
                'value' => $city['city_name'],
                'label' => $city['city_name'],
            ];
        }

        return $data;
    }
}
