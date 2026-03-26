<?php

namespace OnitsukaTigerIndo\Directory\Model\ResourceModel\District;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package OnitsukaTigerIndo\Directory\Model\ResourceModel\District
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected $_eventPrefix = 'directory_country_district_collection';

    protected $_eventObject = 'country_district_collection';

    protected function _construct()
    {
        $this->_init('OnitsukaTigerIndo\Directory\Model\District', 'OnitsukaTigerIndo\Directory\Model\ResourceModel\District');
    }

    /**
     *
     * @return $this
     */
    public function getOptionsCity()
    {
        $this->getSelect()->joinInner(
            ['citiesTable' => $this->getTable('directory_country_cities')],
            'citiesTable.city_id = main_table.city_id',
            'citiesTable.city_name as city_name'
        );

        return $this;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $districts = $this->getOptionsCity()->getData();
        $data = [];
        foreach ($districts as $district) {
            $data[$district['city_name']][] = [
                'value' => $district['district_name'],
                'label' => $district['district_name'],
            ];
        }

        return $data;
    }
}
