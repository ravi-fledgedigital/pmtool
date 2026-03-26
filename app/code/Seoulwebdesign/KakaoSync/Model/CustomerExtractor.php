<?php

namespace Seoulwebdesign\KakaoSync\Model;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;

class CustomerExtractor extends \Magento\Customer\Model\CustomerExtractor
{

    /**
     * Extract customer data from array
     *
     * @param string $formCode
     * @param array $customerData
     * @param array $attributeValues
     * @return CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function extractFromArray(
        $formCode,
        $customerData,
        array $attributeValues = []
    ) {
        $customerForm = $this->formFactory->create(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            $formCode,
            $attributeValues
        );

        $customerData = $customerForm->compactData($customerData);

        $allowedAttributes = $customerForm->getAllowedAttributes();
        $isGroupIdEmpty = !isset($allowedAttributes['group_id']);

        $customerDataObject = $this->customerFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $customerDataObject,
            $customerData,
            CustomerInterface::class
        );

        $store = $this->storeManager->getStore();
        $storeId = $store->getId();

        if ($isGroupIdEmpty) {
            $groupId = isset($customerData['group_id']) ? $customerData['group_id']
                : $this->customerGroupManagement->getDefaultGroup($storeId)->getId();
            
            $customerDataObject->setGroupId($groupId);
        }

        $customerDataObject->setWebsiteId($store->getWebsiteId());
        $customerDataObject->setStoreId($storeId);

        return $customerDataObject;
    }
}
