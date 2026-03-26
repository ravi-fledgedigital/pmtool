<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Vaimo\AepBase\Model\AbandonedCarts\Processor;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Model\Customer\SynchronisePublisher;

class AbandonedCartsPlugin
{
    private ConfigInterface $config;
    private SynchronisePublisher $publisher;
    private CustomerRepositoryInterface $customerRepository;
    private CustomerRegistry $customerRegistry;

    public function __construct(
        SynchronisePublisher $publisher,
        ConfigInterface $config,
        CustomerRepositoryInterface $customerRepository,
        CustomerRegistry $customerRegistry
    ) {
        $this->publisher = $publisher;
        $this->config = $config;
        $this->customerRepository = $customerRepository;
        $this->customerRegistry = $customerRegistry;
    }

    /**
     * @param Processor $subject
     * @param int[] $customersIds
     * @return int[]
     * @throws LocalizedException
     */
    public function afterProcess(Processor $subject, array $customersIds): array
    {
        if (!$this->config->isEnabled()) {
            return $customersIds;
        }

        foreach ($customersIds as $customerId) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $this->publisher->publish($customer);
                $this->customerRegistry->remove($customer->getId());
            } catch (NoSuchEntityException $e) {
                // do nothing
            }
        }

        return $customersIds;
    }
}
