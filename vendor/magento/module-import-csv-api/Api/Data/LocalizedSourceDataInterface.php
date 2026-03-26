<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportCsvApi\Api\Data;

/**
 * {@inheritdoc}
 *
 * Adds locale field in source data
 */
interface LocalizedSourceDataInterface extends SourceDataInterface
{
    /**
     * Get import content locale
     *
     * @return string|null
     */
    public function getLocale(): ?string;

    /**
     * Set import content locale
     *
     * @param string|null $locale
     * @return void
     */
    public function setLocale(?string $locale): void;
}
