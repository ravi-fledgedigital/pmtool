<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\OTAdobeDataLayer\Api;

use Vaimo\OTAdobeDataLayer\Api\Data\DataLayerResponseInterface;

interface DataLayerProviderInterface
{
    /**
     * @param string[] $requestedComponents
     * @return DataLayerResponseInterface
     */
    public function getDataLayer(array $requestedComponents): DataLayerResponseInterface;
}
