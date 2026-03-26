<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\OTAdobeDataLayer\Api\Data;

interface DataLayerResponseInterface
{
    /**
     * @return string
     */
    public function getUserInfo(): string;

    /**
     * @return string
     */
    public function getPageInfo(): string;
}
