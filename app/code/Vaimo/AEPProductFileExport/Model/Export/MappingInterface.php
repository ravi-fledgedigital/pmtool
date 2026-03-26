<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPProductFileExport\Model\Export;

interface MappingInterface
{
    public const MAPPING_TYPE_CALLBACK = 'callback';
    public const MAPPING_TYPE_ATTRIBUTE = 'attribute';

    /**
     * @return string[][]
     */
    public function getMapping(): array;
}
