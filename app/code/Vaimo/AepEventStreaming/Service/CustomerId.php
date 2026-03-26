<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Service;

use Magento\Customer\Api\Data\CustomerInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;

class CustomerId
{
    private ConfigInterface $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function get(CustomerInterface $customer): string
    {
        return $this->getById((int) $customer->getId());
    }

    public function getById(int $customerId): string
    {
        return $this->config->getCustomerIdPrefix() . $customerId;
    }
}
