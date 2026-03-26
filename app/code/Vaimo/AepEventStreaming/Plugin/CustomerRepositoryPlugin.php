<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Model\Customer\SynchronisePublisher;
use Vaimo\AepEventStreaming\Service\Customer\IntegrationHash;

class CustomerRepositoryPlugin
{
    private ExtensionAttributesFactory $extensionFactory;
    private CustomerRegistry $customerRegistry;
    private SynchronisePublisher $publisher;
    private ConfigInterface $config;
    private IntegrationHash $integrationHash;

    public function __construct(
        CustomerRegistry $customerRegistry,
        ExtensionAttributesFactory $extensionFactory,
        SynchronisePublisher $publisher,
        IntegrationHash $integrationHash,
        ConfigInterface $config
    ) {
        $this->customerRegistry = $customerRegistry;
        $this->extensionFactory = $extensionFactory;
        $this->publisher = $publisher;
        $this->integrationHash = $integrationHash;
        $this->config = $config;
    }

    public function afterGetById(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterface $result
    ): CustomerInterface {
        $this->setAepHashExtensionAttribute($result);

        return $result;
    }

    public function afterGet(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result
    ): CustomerInterface {
        $this->setAepHashExtensionAttribute($result);

        return $result;
    }

    private function setAepHashExtensionAttribute(CustomerInterface $customer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        /** @var CustomerExtensionInterface $extensionAttributes */
        $extensionAttributes = $customer->getExtensionAttributes() ?: $this->extensionFactory->create(
            CustomerInterface::class
        );

        if ($extensionAttributes->getAepHash() !== null) {
            return;
        }

        $customerModel = $this->customerRegistry->retrieve($customer->getId());
        $extensionAttributes->setAepHash((string) $customerModel->getData(IntegrationHash::ATTRIBUTE_CODE));
        $customer->setExtensionAttributes($extensionAttributes);
    }

    public function afterSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result
    ): CustomerInterface {
        if (
            !$this->config->isEnabled()
            || $result->getExtensionAttributes()->getAepHash() == $this->integrationHash->calculateHash($result)
        ) {
            return $result;
        }

        $this->publisher->publish($result);

        return $result;
    }
}
