<?php

namespace OnitsukaTiger\Customer\Helper;

use Magento\Eav\Model\Config;
use Magento\Framework\Json\EncoderInterface;
use Magento\Directory\Model\CountryFactory;

/**
 * Class Data helper of customer module
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * Data constructor.
     * @param Config $eavConfig
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param EncoderInterface $jsonEncoder
     * @param CountryFactory $countryFactory
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        Config $eavConfig,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        EncoderInterface $jsonEncoder,
        CountryFactory $countryFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->eavConfig = $eavConfig;
        $this->_addressRepository = $addressRepository;
        $this->jsonEncoder = $jsonEncoder;
        $this->countryFactory = $countryFactory;
        $this->storeManager = $storeManager;
        $this->request = $request;
        parent::__construct($context);
    }

    /**
     * Get customer getAddressType by address value
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @param int $addressValue
     */
    public function getAddressType($addressValue)
    {
        $options = $this->getAllOptions();
        $html = '';
        foreach ($options as $key => $value) {
            $selected = '';

            if ((int)$addressValue == (int)$key) {
                $selected ='selected="selected"';
            }
            if ($key ==0) {
                $key ='';
            }
            $html .='<option value="' . $key . '" ' . $selected . '>' . $value['label'] . '</option>';
        }
        return $html;
    }

    /**
     * Get customer getAddressTypeLabel label by addressValue
     *
     * @param int $addressValue
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddressTypeLabel($addressValue)
    {
        $options = $this->getAllOptions();
        $label = '';
        foreach ($options as $key => $value) {
            if ((int)$addressValue == (int)$key) {
                $label = $value['label'];
            }
        }
        return $label;
    }

    /**
     * Get customer getAddressById label by addressId
     *
     * @param int $addressId
     * @return \Magento\Customer\Api\Data\AddressInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddressById($addressId)
    {
        return $this->_addressRepository->getById($addressId);
    }

    /**
     * Get customer getJsonAddress label by addresses
     *
     * @param obj $addresses
     * @return string
     */
    public function getJsonAddress($addresses)
    {
        $jsonArray = [];
        foreach ($addresses as $address) {
            $address = $this->getAddressById($address->getId());
            $data = [
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'city' => $address->getCity(),
                'street_2' => '',
                'street_3' => '',
                'street_4' => '',
                'street_5' => '',
                'country_id' => $address->getCountryId(),
                'region_id' => $address->getRegionId(),
                'postcode' => $address->getPostcode(),
                'telephone' => $address->getTelephone(),
                'id' => $address->getId(),
                'region' => $address->getRegion()->getRegion(),
                'region_code' => $address->getRegion()->getRegionCode(),
            ];
            foreach ($address->getCustomAttributes() as $customAttribute) {
                $data[$customAttribute->getAttributeCode()] = $customAttribute->getValue();
            }
            $addressValue = [];
            foreach ($address->getStreet() as $key => $value) {
                $key++;
                $data['street_' . $key] = $value;
                $addressValue[] = $value;
            }
            $data['customer-validations-street'] = implode(' ', $addressValue);
            $jsonArray[] = $data;
        }
        return $this->jsonEncoder->encode($jsonArray);
    }

    /**
     * Get country name by $countryCode
     *
     * Using \Magento\Directory\Model\Country to get country name by $countryCode
     *
     * @param string $countryCode
     * @return string
     * @since 102.0.1
     */
    public function getCountryByCode(string $countryCode): string
    {
        /** @var \Magento\Directory\Model\Country $country */
        $country = $this->countryFactory->create();
        return $country->loadByCode($countryCode)->getName();
    }

    /**
     * Get customer sortAddress label by addresses
     *
     * @param obj $addresses
     * @return array
     */
    public function sortAddress($addresses)
    {
        $sortAddress = [];
        if ($addresses) {
            foreach ($addresses as $val) {
                $sortAddress[$val->getId()] = $val;
            }
            ksort($sortAddress);
        }
        return $sortAddress;
    }

    /**
     * Get current store
     *
     * @return obj
     */
    public function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Get full action
     *
     * @return string
     */
    public function getCurrentFullActionName()
    {
        return $this->request->getFullActionName();
    }
}
