<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Api;

interface ConfigInterface
{
    public const SKU_DELIMITER = ';';
    public const STORE_CODE_DELIMITER = '|';

    public const CACHE_TAG = 'aep';

    public function isEnabled(): bool;

    public function isDataAggregationEnabled(): bool;

    public function getPrivateKey(): string;

    public function getCustomerIdPrefix(): string;
}
