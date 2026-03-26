<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Plugin\Customer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Model\Customer\SynchronisePublisher;
use Vaimo\AepEventStreaming\Service\Customer\IntegrationHash;

class AddressRepositoryPlugin
{
    private SynchronisePublisher $publisher;
    private ConfigInterface $config;
    private IntegrationHash $integrationHash;
    private CustomerRepositoryInterface $customerRepository;

    public function __construct(
        SynchronisePublisher $publisher,
        IntegrationHash $integrationHash,
        CustomerRepositoryInterface $customerRepository,
        ConfigInterface $config
    ) {
        $this->publisher = $publisher;
        $this->integrationHash = $integrationHash;
        $this->customerRepository = $customerRepository;
        $this->config = $config;
    }

    public function afterSave(AddressRepositoryInterface $subject, AddressInterface $address): AddressInterface
    {
        if (!$this->config->isEnabled() || !$this->isDefaultAddress($address)) {
            return $address;
        }

        $customer = $this->customerRepository->getById($address->getCustomerId());
        $newHash = $this->integrationHash->calculateHash($customer);
        $currentHash = $customer->getExtensionAttributes()->getAepHash();

        if ($newHash !== $currentHash) {
            $this->publisher->publish($customer);
        }

        return $address;
    }

    private function isDefaultAddress(AddressInterface $address): bool
    {
        return $address->isDefaultShipping() || $address->isDefaultBilling();
    }
}
