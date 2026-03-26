<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\AEPFileExport\Model;

use Magento\ImportExport\Model\Export\Adapter\AbstractAdapter;

interface ExportEntityInterface
{
    /**
     * @return string
     */
    public function export();

    public function setWriter(AbstractAdapter $writer);
}
