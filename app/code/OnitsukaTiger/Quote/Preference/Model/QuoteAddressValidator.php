<?php

namespace OnitsukaTiger\Quote\Preference\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;

class QuoteAddressValidator extends \Magento\Quote\Model\QuoteAddressValidator
{
    private function doValidate(AddressInterface $address, ?int $customerId): void
    {
        $customerId = $customerId ?: $address->getCustomerId();
        //validate customer id
        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            if (!$customer->getId()) {
                throw new NoSuchEntityException(
                    __('Invalid customer id %1', $customerId)
                );
            }
        }

        if ($address->getCustomerAddressId()) {
            //Existing address cannot belong to a guest
            if (!$customerId) {
                throw new NoSuchEntityException(
                    __('Invalid customer address id %1', $address->getCustomerAddressId())
                );
            }
            //Validating address ID
            try {
                $this->addressRepository->getById($address->getCustomerAddressId());
            } catch (NoSuchEntityException $e) {
                throw new NoSuchEntityException(
                    __('Invalid address id %1', $address->getId())
                );
            }
            //Finding available customer's addresses
            $applicableAddressIds = array_map(function ($address) {
                /** @var \Magento\Customer\Api\Data\AddressInterface $address */
                return $address->getId();
            }, $this->customerRepository->getById($customerId)->getAddresses());
            if (!in_array($address->getCustomerAddressId(), $applicableAddressIds)) {
                throw new NoSuchEntityException(
                    __('Invalid customer address id %1', $address->getCustomerAddressId())
                );
            }
        }
    }
}