<?php

namespace OnitsukaTigerKorea\Customer\Model;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 * Class Address
 * @package OnitsukaTigerKorea\Customer\Model
 */
class Address extends \Magento\Customer\Model\Address
{
    /**
     * @var \Magento\Customer\Model\Address\CustomAttributeListInterface
     */
    private $attributeList;

    /**
     * @var Data
     */
    protected $dataHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        AddressMetadataInterface $metadataService,
        AddressInterfaceFactory $addressDataFactory,
        RegionInterfaceFactory $regionDataFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        CustomerFactory $customerFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        Data $dataHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $directoryData, $eavConfig, $addressConfig, $regionFactory, $countryFactory, $metadataService, $addressDataFactory, $regionDataFactory, $dataObjectHelper, $customerFactory, $dataProcessor, $indexerRegistry, $resource, $resourceCollection, $data);
    }

    /**
     * Update Model with the data from Data Interface
     *
     * @param AddressInterface $address
     * @return \Magento\Customer\Model\Address
     * Use Api/RepositoryInterface for the operations in the Data Interfaces. Don't rely on Address Model
     */
    public function updateData(AddressInterface $address)
    {
        if ($this->dataHelper->isCustomerEnabled()) {
            // Set all attributes
            $attributes = $this->dataProcessor
                ->buildOutputDataArray($address, \Magento\Customer\Api\Data\AddressInterface::class);

            foreach ($attributes as $attributeCode => $attributeData) {
                if (AddressInterface::REGION === $attributeCode) {
                    $this->setRegion($address->getRegion()->getRegion());
                    $this->setRegionCode($address->getRegion()->getRegionCode());
                    $this->setRegionId($address->getRegion()->getRegionId());
                } elseif (AddressInterface::LASTNAME === $attributeCode) {
                    $this->setLastname('성');
                } elseif (AddressInterface::CITY === $attributeCode && $attributeData == '') {
                    $this->setCity('&nbsp');
                } else {
                    $this->setDataUsingMethod($attributeCode, $attributeData);
                }
            }
            // Need to explicitly set this due to discrepancy in the keys between model and data object
            $this->setIsDefaultBilling($address->isDefaultBilling());
            $this->setIsDefaultShipping($address->isDefaultShipping());
            $customAttributes = $address->getCustomAttributes();
            if ($customAttributes !== null) {
                foreach ($customAttributes as $attribute) {
                    $this->setData($attribute->getAttributeCode(), $attribute->getValue());
                }
            }

            return $this;
        } else {
            return parent::updateData($address);
        }

    }

    /**
     * Get new AttributeList dependency for application code.
     *
     * @return \Magento\Customer\Model\Address\CustomAttributeListInterface
     * @deprecated 100.0.6
     */
    private function getAttributeList()
    {
        if (!$this->attributeList) {
            $this->attributeList = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Customer\Model\Address\CustomAttributeListInterface::class
            );
        }
        return $this->attributeList;
    }
}
