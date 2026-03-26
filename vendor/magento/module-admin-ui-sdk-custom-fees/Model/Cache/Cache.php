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

namespace Magento\AdminUiSdkCustomFees\Model\Cache;

use Magento\CommerceBackendUix\Model\Cache\Cache as CommerceBackendUixCache;
use Magento\CommerceBackendUix\Model\Cache\RegistrationKeys;
use Magento\CommerceBackendUix\Model\Cache\Type\CacheType;

/**
 * Cache for custom fees
 */
class Cache
{
    /**
     * @param CommerceBackendUixCache $cache
     */
    public function __construct(private CommerceBackendUixCache $cache)
    {
    }

    /**
     * Get registered order custom fees
     *
     * @return array
     */
    public function getOrderCustomFees(): array
    {
        $registrations = $this->cache->getRegistrations();
        return $registrations['order']['customFees'] ?? [];
    }
}
