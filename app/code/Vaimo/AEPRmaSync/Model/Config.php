<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Model;

use Vaimo\AepEventStreaming\Model\Config as AepConfig;
use Vaimo\AEPRmaSync\Api\ConfigInterface;

class Config extends AepConfig implements ConfigInterface
{
    private const XPATH_RMA_ENDPOINT = 'aep_event_streaming/rma_sync/endpoint';
    private const XPATH_RMA_SCHEMA_ID = 'aep_event_streaming/rma_sync/schema_id';
    private const XPATH_RMA_DATASET_ID = 'aep_event_streaming/rma_sync/dataset_id';
    private const XPATH_RMA_FLOW_ID = 'aep_event_streaming/rma_sync/flow_id';

    public function getRmaEndpoint(): string
    {
        return $this->addDebugParam($this->scopeConfig->getValue(self::XPATH_RMA_ENDPOINT));
    }

    public function getRmaSchemaId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_RMA_SCHEMA_ID);
    }

    public function getRmaDatasetId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_RMA_DATASET_ID);
    }

    public function getRmaFlowId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_RMA_FLOW_ID);
    }
}