<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\AEPProductFileExport\Model;

interface ConfigProviderInterface
{
    public function getFolderPath(): string;

    public function getFilename(): string;

    public function isSchedulerEnabled(): bool;
}
