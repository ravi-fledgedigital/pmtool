<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\ResourceModel;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order as ResourceModel;
use Vaimo\AepEventStreaming\Api\Data\IngestRecordInterface;

class Order
{
    private ResourceModel $resource;

    public function __construct(ResourceModel $resource)
    {
        $this->resource = $resource;
    }

    public function updateLastActionId(int $orderId, string $lastActionId): void
    {
        $this->resource->getConnection()->update(
            $this->resource->getMainTable(),
            [IngestRecordInterface::ATTRIBUTE_CODE => $lastActionId],
            [OrderInterface::ENTITY_ID . ' = ?' => $orderId]
        );
    }
}
