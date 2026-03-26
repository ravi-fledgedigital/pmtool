<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Api;

use Vaimo\AepEventStreaming\Api\ConfigInterface as AepConfigInterface;

interface ConfigInterface extends AepConfigInterface
{
    public function getRmaSchemaId(): string;

    public function getRmaEndpoint(): string;

    public function getRmaDatasetId(): string;

    public function getRmaFlowId(): string;
}