<?php
/** phpcs:ignoreFile */
namespace OnitsukaTigerIndo\Checkout\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\DirectoryDataProcessor as MageDirectoryDataProcessor;
use OnitsukaTigerIndo\Directory\Model\ResourceModel\City\CollectionFactory as CityCollection;
use OnitsukaTigerIndo\Directory\Model\ResourceModel\District\CollectionFactory as DistrictCollection;

/**
 * Class DirectoryDataProcessor
 *
 * @package OnitsukaTigerIndo\Checkout\Plugin\Checkout\Block\Checkout
 */
class DirectoryDataProcessor
{
    /**
     * @var DistrictCollection
     */
    protected $districtCollection;

    /**
     * @var CityCollection
     */
    protected $cityCollection;

    /**
     * DirectoryDataProcessor constructor.
     * @param DistrictCollection $districtCollection
     * @param CityCollection $cityCollection
     */
    public function __construct(
        DistrictCollection $districtCollection,
        CityCollection $cityCollection
    ) {
        $this->districtCollection = $districtCollection;
        $this->cityCollection = $cityCollection;
    }

    /**
     * @param MageDirectoryDataProcessor $subject
     * @param $result
     * @return mixed
     */
    public function afterProcess(MageDirectoryDataProcessor $subject, $result)
    {
        $result['components']['checkoutProvider']['dictionaries']['city'] = $this->getCitiesOptions();
        $result['components']['checkoutProvider']['dictionaries']['district'] = $this->getDistrictOptions();

        return $result;
    }

    /**
     * @return array
     */
    public function getCitiesOptions()
    {
        $data[] = [
            'title' => __('Please select a city.'),
            'value' => '',
            'region_id' => '',
            'label' => __('Please select a city.')
        ];

        $collection = $this->cityCollection->create()->getOptionsProvince();
        if ($collection->getSize()) {
            foreach ($collection->getData() as $city) {
                $data[] = [
                    'title' => __($city['city_name']),
                    'value' => $city['city_name'],
                    'region_id' => $city['region_id'],
                    'label' => __($city['city_name'])
                ];
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getDistrictOptions()
    {
        $data[] = [
            'title' => __('Please select a district.'),
            'value' => '',
            'city_id' => '',
            'label' => __('Please select a district.')
        ];

        $collection = $this->districtCollection->create()->getOptionsCity();
        if ($collection->getSize()) {
            foreach ($collection->getData() as $district) {
                $data[] = [
                    'title' => __($district['district_name']),
                    'value' => $district['district_name'],
                    'city_name' => $district['city_name'],
                    'label' => __($district['district_name'])
                ];
            }
        }

        return $data;
    }
}
