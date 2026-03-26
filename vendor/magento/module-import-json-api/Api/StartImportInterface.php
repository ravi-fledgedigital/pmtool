<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportJsonApi\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\ImportJsonApi\Api\Data\SourceDataInterface;

/**
 * Start import JSON operation interface.
 *
 * @api
 */
interface StartImportInterface
{
    /**
     * Starts import operation.
     *
     * @param SourceDataInterface $source
     * @return string[]
     * @throws LocalizedException
     */
    public function execute(
        SourceDataInterface $source
    ): array;
}
