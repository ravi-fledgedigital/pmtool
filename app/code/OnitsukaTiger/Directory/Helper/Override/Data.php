<?php

namespace OnitsukaTiger\Directory\Helper\Override;


use Magento\Directory\Model\AllowedCountries;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 */
class Data extends \Magento\Directory\Helper\Data
{

    /**
     * Retrieve regions data
     *
     * @return array
     */
    public function getRegionData()
    {
        $scope = $this->getCurrentScope();
        $allowedCountries = $this->scopeConfig->getValue(
            AllowedCountries::ALLOWED_COUNTRIES_PATH,
            $scope['type'],
            $scope['value']
        );
        $countryIds = explode(',', $allowedCountries);
        $collection = $this->_regCollectionFactory->create();
        $collection->addCountryFilter($countryIds)->load();
        $regions = [
            'config' => [
                'show_all_regions' => $this->isShowNonRequiredState(),
                'regions_required' => $this->getCountriesWithStatesRequired(),
            ],
        ];
        foreach ($collection as $region) {
            /** @var $region \Magento\Directory\Model\Region */
            if (!$region->getRegionId()) {
                continue;
            }
            $regions[$region->getCountryId()][$region->getRegionId()] = [
                'code' => $region->getCode(),
                'name' => (string)__($region->getName()),
            ];
        }
        return $regions;
    }
    /**
     * Get current scope from request
     *
     * @return array
     */
    private function getCurrentScope(): array
    {

        return [
            'type' => ScopeInterface::SCOPE_WEBSITE,
            'value' => null,
        ];
    }
}
