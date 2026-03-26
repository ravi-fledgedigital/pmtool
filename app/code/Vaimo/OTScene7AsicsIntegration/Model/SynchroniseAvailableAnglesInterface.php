<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7AsicsIntegration\Model;

interface SynchroniseAvailableAnglesInterface
{
    /**
     * Get all available images angles form API, and save them into magento attribute.
     */
    public function execute($lastTimeUpdate = "", string $item = "", $output = ""): void;
}
