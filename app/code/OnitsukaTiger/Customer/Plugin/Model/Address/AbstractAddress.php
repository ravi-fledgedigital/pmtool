<?php
namespace OnitsukaTiger\Customer\Plugin\Model\Address;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Data\Address as AddressData;
use Magento\Framework\App\ObjectManager;

class AbstractAddress {

    /**
     * @var \Magento\Framework\View\Element\Template|mixed
     */
    private mixed $block;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private  $dataObjectHelper;

    /**
     * @var RegionInterfaceFactory
     */
    private  $regionDataFactory;

    /**
     * @var AddressMetadataInterface
     */
    private  $metadataService;

    /**
     * @var AddressInterfaceFactory
     */
    private  $addressDataFactory;

    /**
     * @param \Magento\Framework\View\Element\Template $block
     */
    public function __construct(
        \Magento\Framework\View\Element\Template $block,
        AddressMetadataInterface $metadataService,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        AddressInterfaceFactory $addressDataFactory,
        RegionInterfaceFactory $regionDataFactory
    ) {
        $this->metadataService = $metadataService;
        $this->regionDataFactory = $regionDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->addressDataFactory = $addressDataFactory;
        $this->block = $block ?: ObjectManager::getInstance()->get(\Magento\Framework\View\Element\Template::class);
    }
    public function aroundGetDataModel(\Magento\Customer\Model\Address\AbstractAddress $subject, callable $proceed, $defaultBillingAddressId = null, $defaultShippingAddressId = null)
    {
        $addressId = $subject->getId();

        $attributes = $this->metadataService->getAllAttributesMetadata();
        $addressData = [];
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if ($subject->getData($code) !== null) {
                if ($code === AddressInterface::STREET) {
                    $addressData[$code] = $this->block->escapeHtml($subject->getDataUsingMethod($code));
                } else {
                    $addressData[$code] = $subject->getData($code);
                }
            }
        }

        /** @var RegionInterface $region */
        $region = $this->regionDataFactory->create();
        $region->setRegion($subject->getRegion())
            ->setRegionCode($subject->getRegionCode())
            ->setRegionId($subject->getRegionId());

        $addressData[AddressData::REGION] = $region;

        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            $addressData,
            \Magento\Customer\Api\Data\AddressInterface::class
        );
        if ($addressId) {
            $addressDataObject->setId($addressId);
        }

        if ($subject->getCustomerId() || $subject->getParentId()) {
            $customerId = $subject->getCustomerId() ?: $subject->getParentId();
            $addressDataObject->setCustomerId($customerId);
            if ($defaultBillingAddressId == $addressId) {
                $addressDataObject->setIsDefaultBilling(true);
            }
            if ($defaultShippingAddressId == $addressId) {
                $addressDataObject->setIsDefaultShipping(true);
            }
        }

        return $addressDataObject;
    }
}
