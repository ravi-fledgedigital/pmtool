<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model\Cache;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;

/**
 * Cache invalidator class for Admin UI SDK refresh button
 */
class CacheInvalidator
{
    /**
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     * @param array $cacheTypes
     */
    public function __construct(
        private TypeListInterface $cacheTypeList,
        private Pool $cacheFrontendPool,
        private array $cacheTypes = []
    ) {
    }

    /**
     * Invalidate cache based on cache types
     *
     * @return bool
     */
    public function invalidate(): bool
    {
        foreach ($this->cacheTypes as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            if (!$cacheFrontend->getBackend()->clean()) {
                return false;
            }
        }
        return true;
    }
}
