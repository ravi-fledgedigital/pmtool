<?php
/** phpcs:ignoreFile */
namespace OnitsukaTigerIndo\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTigerIndo\Directory\Model\ResourceModel\District\CollectionFactory as DistrictCollection;
use OnitsukaTigerIndo\Directory\Model\ResourceModel\City\CollectionFactory as CityCollection;

/**
 * Class Data
 * OnitsukaTigerIndo\Customer\Helper\Data
 */
class Data extends AbstractHelper
{
    const PATH_ENABLE = 'customer_indo/customer_address/enabled';

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var DistrictCollection
     */
    protected $districtCollection;

    /**
     * @var CityCollection
     */
    protected $cityCollection;

    /**
     * Data constructor.
     * @param Context $context
     * @param SerializerInterface $serializer
     * @param DistrictCollection $districtCollection
     * @param CityCollection $cityCollection
     */
    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        DistrictCollection $districtCollection,
        CityCollection $cityCollection
    ) {
        parent::__construct($context);
        $this->serializer = $serializer;
        $this->districtCollection = $districtCollection;
        $this->cityCollection = $cityCollection;
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function isEnableModule($storeId = null)
    {
        return $this->scopeConfig->getValue(self::PATH_ENABLE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return string
     */
    public function getJsonAddressData(): string
    {
        return $this->serializer->serialize([
            'cities' =>$this->cityCollection->create()->toOptionArray(),
            'districts' => $this->districtCollection->create()->toOptionArray()
        ]);
    }
}
