<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Service\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Vaimo\AepEventStreaming\Model\ResourceModel\Customer as ResourceModel;

class IntegrationHash
{
    public const ATTRIBUTE_CODE = 'aep_hash';

    private AepMapper $mapper;
    private ResourceModel $resourceModel;

    public function __construct(
        AepMapper $mapper,
        ResourceModel $resourceModel
    ) {
        $this->mapper = $mapper;
        $this->resourceModel = $resourceModel;
    }

    public function calculateHash(CustomerInterface $customer): string
    {
        $map = $this->mapper->map($customer);
        unset($map['modifiedDate']);

        // phpcs:ignore Magento2.Security.InsecureFunction.FoundWithAlternative
        $hashString = \serialize($map);

        return \hash('md5', $hashString);
    }

    public function updateIntegrationHash(int $customerId, string $hash): void
    {
        $this->resourceModel->updateAepAttributes($customerId, $hash);
    }
}
