<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Service\Customer;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\OptionInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\ResourceModel\Subscriber as SubscriberResource;
use Magento\Newsletter\Model\Subscriber;
use Vaimo\AepEventStreaming\Api\AepMapperInterface;
use Vaimo\AepEventStreaming\Service\CustomerId;

class AepMapper implements AepMapperInterface
{
    private const ATTRIBUTE_CODE_GENDER = 'gender';

    private CustomerMetadataInterface $customerMetadata;
    private DirectoryHelper $directoryHelper;
    private SubscriberResource $subscriberResource;
    private CustomerId $customerId;
    /**
     * @var Vaimo\AepEventStreaming\Helper\Data
     */
    protected $helper;

    /**
     * @param Vaimo\AepEventStreaming\Helper\Data $helper
     */
    public function __construct(
        CustomerMetadataInterface $customerMetadata,
        DirectoryHelper $directoryHelper,
        CustomerId $customerId,
        SubscriberResource $subscriberResource,
        \Vaimo\AepEventStreaming\Helper\Data $helper
    ) {
        $this->customerMetadata = $customerMetadata;
        $this->directoryHelper = $directoryHelper;
        $this->customerId = $customerId;
        $this->subscriberResource = $subscriberResource;
        $this->helper = $helper;
    }

    // phpcs:ignore  SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function map(CustomerInterface $customer): array
    {
        $extension = $customer->getExtensionAttributes();
        

       // $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/aep/customer_sync_data.log");
       // $logger = new \Zend_Log();
       // $logger->addWriter($writer);
        
        $configWebsiteIds = $this->helper->getExcludeWebsiteStreaming();

        //$logger->info("==== AepMapper customer  configWebsiteIds ====");
        //$logger->info(print_r($configWebsiteIds, true));

        $websiteIds = [];
        if(!empty($configWebsiteIds)) {
            $websiteIds = explode(',', $configWebsiteIds);
        }

       // $logger->info("==== AepMapper customer exploded websiteIds ====");
       // $logger->info(print_r($websiteIds, true));

        //$logger->info("==================================");

        //$logger->info("AepMapper customer customer website");
        //$logger->info($customer->getWebsiteId());

        //$logger->info("=== AepMapper customer in not in_array ===");
        //if (!in_array($customer->getWebsiteId(), $websiteIds)) {
          //  $logger->info("=== if called ===");
       // }else{
           // $logger->info("=== else called ===");
       // }

        $customerData = [];

        if (!in_array($customer->getWebsiteId(), $websiteIds)) {
            $customerData = [
                'billingAddress' => $this->getBillingAddress($customer),
                'dob' => $customer->getDob() ?: null,
                'customerId' => $this->customerId->get($customer),
                'emailAddress' => $customer->getEmail(),
                'firstName' => $customer->getFirstname(),
                'gender' => $customer->getGender() ? $this->getGender((int) $customer->getGender()) : null,
                'lastName' => $customer->getLastname(),
                'shippingAddress' => $this->getShippingAddress($customer),
                'modifiedDate' => $this->convertDateTimeFormat($customer->getUpdatedAt()),
                'totalCouponsCnt' => (int) $extension->getAepTotalCouponCount(),
                'totalOrderAmt' => (float) $extension->getAepTotalOrderAmt(),
                'totalOrderCnt' => (int) $extension->getAepTotalOrderCnt(),
                'totalReturnOrderAmt' => (float) $extension->getAepTotalReturnOrderAmt(),
                'totalReturnOrderCnt' => (int) $extension->getAepTotalReturnOrderCnt(),
                'wishlistProducts' =>  $extension->getAepWhishlistProducts(),
                'wishlistModifiedDateTime' =>  $this->convertDateTimeFormat($extension->getAepWishlistModifiedDatetime()),
                'cartAbandonedProducts' => $extension->getAepCartAbandonedProducts(),
                'cartModifiedDateTime' => $this->convertDateTimeFormat($extension->getAepCartModifiedDatetime()),
                'lastOrderDate' => $this->convertDateFormat($extension->getAepLastOrderDate()),
                'lifetimeValueAmt' => $extension->getAepLifetimeValueAmt(),
                'firstOrderDate' => $this->convertDateFormat($extension->getAepFirstOrderDate()),
                'marketingPreferences' => $this->getMarketingPreference($customer),
                'baseCountry' => $this->directoryHelper->getDefaultCountry($customer->getStoreId()),
                'shoeFavoriteSize' =>  0, // @todo aggregated field
                'accessoriesFavoriteSize' => 0, // @todo aggregated field
                'clothsFavoriteSize' => 0, // @todo aggregated field
            ];
        }

        return [strtolower($this->directoryHelper->getDefaultCountry($customer->getStoreId())) => $customerData];
    }

    private function getGender(?int $genderId): ?string
    {
        if (!$genderId) {
            return null;
        }

        try {
            $options = $this->getGenderOptions();

            foreach ($options as $option) {
                if ($option->getValue() == $genderId) {
                    return $option->getLabel();
                }
            }
        } catch (LocalizedException $e) {
            return null;
        }

        return null;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function getMarketingPreference(CustomerInterface $customer): array
    {
        $subscriptionStatus = $customer->getExtensionAttributes()->getIsSubscribed();

        // magento created plugin to set this value only for getById method so sometimes this value is missing
        if ($subscriptionStatus === null) {
            $subscriptionStatus = $this->isSubscribed($customer);
        }

        return [
            [
                'name' => 'Email',
                'val'  => $subscriptionStatus,
            ],
        ];
    }

    private function isSubscribed(CustomerInterface $customer): bool
    {
        $subscriber = $this->subscriberResource->loadByCustomerData($customer);

        return isset($subscriber['subscriber_status'])
            && $subscriber['subscriber_status'] == Subscriber::STATUS_SUBSCRIBED;
    }

    /**
     * @param CustomerInterface $customer
     * @return string[][]
     */
    private function getIsSubscribed(CustomerInterface $customer): array
    {
        return $this->subscriberResource->loadByCustomerData($customer);
    }

    /**
     * @return OptionInterface[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getGenderOptions(): array
    {
        $attribute = $this->customerMetadata->getAttributeMetadata(self::ATTRIBUTE_CODE_GENDER);

        return $attribute->getOptions();
    }

    private function convertDateFormat(?string $dateString): ?string
    {
        if ($dateString === null) {
            return null;
        }

        return \DateTime::createFromFormat('Y-m-d H:i:s', $dateString)
            ->format(self::AEP_DATE_FORMAT);
    }

    private function convertDateTimeFormat(?string $dateTimeString): ?string
    {
        if ($dateTimeString === null) {
            return null;
        }

        return \DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeString)
            ->format(self::AEP_DATETIME_FORMAT);
    }

    /**
     * @param CustomerInterface $customer
     * @return string[]|null
     */
    private function getBillingAddress(CustomerInterface $customer): ?array
    {
        $address = $this->getAddressById($customer, (int) $customer->getDefaultBilling());

        if ($address) {
            return $this->addressToArray($address);
        }

        return null;
    }

    private function getAddressById(CustomerInterface $customer, int $addressId): ?AddressInterface
    {
        foreach ($customer->getAddresses() as $address) {
            if ((int) $address->getId() === $addressId) {
                return $address;
            }
        }

        return null;
    }

    /**
     * @param CustomerInterface $customer
     * @return string[]|null
     */
    private function getShippingAddress(CustomerInterface $customer): ?array
    {
        $address = $this->getAddressById($customer, (int) $customer->getDefaultShipping());

        if ($address) {
            return $this->addressToArray($address);
        }

        return null;
    }

    /**
     * @param AddressInterface $address
     * @return string[]
     */
    private function addressToArray(AddressInterface $address): array
    {
        return [
            'city' => $address->getCity(),
            'country' => $address->getCountryId(),
            'postCode' => $address->getPostcode(),
            'region' => ($address->getRegion() ? $address->getRegion()->getRegion() : ''),
            'street' => is_array($address->getStreet()) ? implode(", ", $address->getStreet()) : '',
        ];
    }
}