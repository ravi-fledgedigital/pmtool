<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportCsvApi\Api;

use Magento\Framework\Validation\ValidationException;
use Magento\ImportCsvApi\Api\Data\LocalizedSourceDataInterface;

/**
 * Imports localized source data
 */
interface ImportLocalizedSourceDataInterface
{
    /**
     * Start import operation
     *
     * @param LocalizedSourceDataInterface $source Describes how to retrieve data from data source
     * @return string[]
     * @throws ValidationException
     */
    public function execute(
        LocalizedSourceDataInterface $source
    ): array;
}
