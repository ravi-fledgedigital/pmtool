<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 *  ADOBE CONFIDENTIAL
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

declare(strict_types=1);

namespace Magento\CheckoutAddressSearch\Plugin\Customer\Model\Address;

use Magento\Customer\Model\Address\CustomerAddressDataProvider as AddressDataProvider;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CheckoutAddressSearch\Model\Config as CustomerAddressSearchConfig;

class CustomerAddressDataProvider
{
    /**
     * @var CustomerAddressSearchConfig
     */
    private CustomerAddressSearchConfig $config;

    /**
     * @param CustomerAddressSearchConfig $config
     */
    public function __construct(CustomerAddressSearchConfig $config)
    {
        $this->config = $config;
    }

    /**
     * If address search is enabled we should limit the number of addresses required
     *
     * @param AddressDataProvider $subject
     * @param CustomerInterface $customer
     * @param int|null $addressLimit
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGetAddressDataByCustomer(
        AddressDataProvider $subject,
        CustomerInterface   $customer,
        ?int                $addressLimit = null
    ): array {
        if ($this->config->isEnabledAddressSearch()) {
            $addressLimit = $this->config->getSearchLimit();
        }

        return [$customer, $addressLimit];
    }
}
