<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\OTAdobeDataLayer\Api;

interface ConfigInterface
{
    public function isEnabled(): bool;

    public function getLaunchEmbedCode(): ?string;

    public function getLoggedInSite(): ?string;

    public function getLoggedInRegion(): ?string;
}
