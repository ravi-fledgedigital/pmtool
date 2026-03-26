<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPStockFileExport\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Vaimo\AEPFileExport\Model\Service\Export;

class ProxyWrapper
{
    private State $state;
    private Export $export;

    public function __construct(
        State $state,
        Export $export
    ) {
        $this->state = $state;
        $this->export = $export;
    }

    public function execute(): void
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $this->export->execute();
    }
}
