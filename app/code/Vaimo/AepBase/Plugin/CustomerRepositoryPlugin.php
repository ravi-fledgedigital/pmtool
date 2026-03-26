<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Vaimo\AepBase\Api\ConfigInterface;
use Vaimo\AepBase\Setup\Patch\Data\CustomerDataAggregationAttributes as AepAttributes;

class CustomerRepositoryPlugin
{
    private ExtensionAttributesFactory $extensionFactory;
    private CustomerRegistry $customerRegistry;
    private ConfigInterface $config;

    public function __construct(
        CustomerRegistry $customerRegistry,
        ExtensionAttributesFactory $extensionFactory,
        ConfigInterface $config
    ) {
        $this->customerRegistry = $customerRegistry;
        $this->extensionFactory = $extensionFactory;
        $this->config = $config;
    }

    public function afterGetById(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterface $result
    ): CustomerInterface {
        $this->setAepAttributes($result);

        return $result;
    }

    public function afterGet(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result
    ): CustomerInterface {
        $this->setAepAttributes($result);

        return $result;
    }

    private function setAepAttributes(CustomerInterface $customer): void
    {
        if (!$this->config->isDataAggregationEnabled()) {
            return;
        }

        /** @var CustomerExtensionInterface $extensionAttributes */
        $extensionAttributes = $customer->getExtensionAttributes() ?: $this->extensionFactory->create(
            CustomerInterface::class
        );

        if ($extensionAttributes->getIsAepDataSet()) {
            return;
        }

        $customerModel = $this->customerRegistry->retrieve($customer->getId());
        $extensionAttributes->setIsAepDataSet(true);

        foreach (AepAttributes::ATTRIBUTE_CODES as $attributeCode) {
            $method = 'set' . str_replace('_', '', ucwords($attributeCode, '_'));
            $extensionAttributes->$method($customerModel->getData($attributeCode));
        }

        $customer->setExtensionAttributes($extensionAttributes);
    }
}
