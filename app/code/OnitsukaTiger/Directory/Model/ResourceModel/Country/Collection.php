<?php
namespace OnitsukaTiger\Directory\Model\ResourceModel\Country;

use Magento\Directory\Model\AllowedCountries;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

class Collection extends \Magento\Directory\Model\ResourceModel\Country\Collection
{
    /**
     * Locale model
     *
     * @var \Magento\Framework\Locale\ListsInterface
     */
    protected $_localeLists;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Directory\Model\ResourceModel\CountryFactory
     */
    protected $_countryFactory;

    /**
     * Array utils object
     *
     * @var \Magento\Framework\Stdlib\ArrayUtils
     */
    protected $_arrayUtils;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $helperData;

    /**
     * @var AllowedCountries
     */
    private $allowedCountriesReader;

    /**
     * @var string[]
     * @since 100.1.0
     */
    protected $countriesWithNotRequiredStates;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Locale\ListsInterface $localeLists
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Model\ResourceModel\CountryFactory $countryFactory
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\App\Helper\AbstractHelper $helperData
     * @param array $countriesWithNotRequiredStates
     * @param mixed $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @param \Magento\Store\Model\StoreManagerInterface|null $storeManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Locale\ListsInterface $localeLists,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\ResourceModel\CountryFactory $countryFactory,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\App\Helper\AbstractHelper $helperData,
        array $countriesWithNotRequiredStates = [],
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null,
        \Magento\Store\Model\StoreManagerInterface $storeManager = null
    ) {
        parent::__construct( $entityFactory, $logger, $fetchStrategy, $eventManager, $localeLists, $scopeConfig, $countryFactory, $arrayUtils, $localeResolver, $helperData, $countriesWithNotRequiredStates ,$connection = null, $resource = null, $storeManager = null);
        $this->_scopeConfig = $scopeConfig;
        $this->_localeLists = $localeLists;
        $this->_localeResolver = $localeResolver;
        $this->_countryFactory = $countryFactory;
        $this->_arrayUtils = $arrayUtils;
        $this->helperData = $helperData;
        $this->countriesWithNotRequiredStates = $countriesWithNotRequiredStates;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(
            \Magento\Store\Model\StoreManagerInterface::class
        );
    }
    /**
     * Convert collection items to select options array
     *
     * @param string|boolean $emptyLabel
     * @return array
     */
    public function toOptionArray($emptyLabel = ' ')
    {
        $options = $this->_toOptionArray('country_id', 'name', ['title' => 'iso2_code']);
        $sort = $this->getSort($options);

        $this->_arrayUtils->ksortMultibyte($sort, $this->_localeResolver->getLocale());
        foreach (array_reverse($this->_foregroundCountries) as $foregroundCountry) {
            $name = array_search($foregroundCountry, $sort);
            if ($name) {
                unset($sort[$name]);
                $sort = [$name => $foregroundCountry] + $sort;
            }
        }
        $isRegionVisible = (bool)$this->helperData->isShowNonRequiredState();

        $options = [];
        foreach ($sort as $label => $value) {
            $option = ['value' => $value, 'label' => $label];
            if ($this->helperData->isRegionRequired($value)) {
                $option['is_region_required'] = true;
            } else {
                $option['is_region_visible'] = $isRegionVisible;
            }
            if ($this->helperData->isZipCodeOptional($value)) {
                $option['is_zipcode_optional'] = true;
            }
            $options[] = $option;
        }
        if ($emptyLabel !== false && count($options) > 1) {
            array_unshift($options, ['value' => '', 'label' => $emptyLabel]);
        }

        $this->addDefaultCountryToOptions($options);

        return $options;
    }
    /**
     * Adds default country to options
     *
     * @param array $options
     * @return void
     */
    private function addDefaultCountryToOptions(array &$options)
    {
        $defaultCountry = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            $defaultCountryConfig = $this->_scopeConfig->getValue(
                \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_COUNTRY,
                ScopeInterface::SCOPE_WEBSITES,
                $website
            );
            $defaultCountry[$defaultCountryConfig][] = $website->getId();
        }

        foreach ($options as $key => $option) {
            if (isset($defaultCountry[$option['value']])) {
                $options[$key]['is_default'] = !empty($defaultCountry[$option['value']]);
            }
        }
    }
    /**
     * Get sort
     *
     * @param array $options
     * @return array
     */
    private function getSort(array $options): array
    {
        $sort = [];
        foreach ($options as $data) {
            $name = (string)$this->_localeLists->getCountryTranslation($data['value'],'en_US');
            if (!empty($name)) {
                $sort[$name] = $data['value'];
            }
        }

        return $sort;
    }
}
