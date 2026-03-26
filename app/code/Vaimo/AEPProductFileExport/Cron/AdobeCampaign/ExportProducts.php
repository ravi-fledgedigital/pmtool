<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPProductFileExport\Cron\AdobeCampaign;

use Vaimo\AEPFileExport\Model\ExportConfigProviderInterface;
use Vaimo\AEPFileExport\Model\Service\Export;

class ExportProducts
{
    private ExportConfigProviderInterface $configProvider;
    private Export $export;

    public function __construct(ExportConfigProviderInterface $configProvider, Export $export)
    {
        $this->configProvider = $configProvider;
        $this->export = $export;
    }

    public function execute()
    {
        if (!$this->configProvider->isSchedulerEnabled()) {
            return;
        }

        $this->export->execute();
    }
}
