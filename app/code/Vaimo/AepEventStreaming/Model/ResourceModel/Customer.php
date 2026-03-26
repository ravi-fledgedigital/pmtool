<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\ResourceModel;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Vaimo\AepEventStreaming\Api\Data\IngestRecordInterface;
use Vaimo\AepEventStreaming\Service\Customer\IntegrationHash;

class Customer
{
    private ResourceConnection $resource;
    private EavConfig $eavConfig;

    public function __construct(ResourceConnection $resource, EavConfig $eavConfig)
    {
        $this->resource = $resource;
        $this->eavConfig = $eavConfig;
    }

    public function updateAepAttributes(
        int $customerId,
        ?string $integrationHash = null,
        ?string $lastActionId = null
    ): void {
        if ($integrationHash === null && $lastActionId === null) {
            return;
        }

        $data = [];

        if ($integrationHash !== null) {
            $data[] = [
                'entity_id' => $customerId,
                'attribute_id' => $this->getIntegrationHashAttributeId(),
                'value' => $integrationHash,
            ];
        }

        if ($lastActionId !== null) {
            $data[] = [
                'entity_id' => $customerId,
                'attribute_id' => $this->getLastActionIdAttributeId(),
                'value' => $lastActionId,
            ];
        }

        $this->resource->getConnection()->insertOnDuplicate(
            $this->getAttributeTableName(),
            $data
        );
    }

    private function getIntegrationHashAttributeId(): int
    {
        return (int) $this->eavConfig->getAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            IntegrationHash::ATTRIBUTE_CODE
        )->getId();
    }

    private function getLastActionIdAttributeId(): int
    {
        return (int) $this->eavConfig->getAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            IngestRecordInterface::ATTRIBUTE_CODE
        )->getId();
    }

    /**
     * Both attributes are stored in the same table
     */
    private function getAttributeTableName(): string
    {
        return $this->eavConfig->getAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            IngestRecordInterface::ATTRIBUTE_CODE
        )->getBackendTable();
    }
}
