<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Service;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\CacheInterface;

class CacheService
{
    private $serializer;

    private $cache;

    public function __construct(
        Json $serializer,
        CacheInterface $cache
    ) {
        $this->serializer   = $serializer;
        $this->cache        = $cache;
    }
    
    private function getCacheKey(string $instance, string $dataKey)
    {
        $key = mb_strtoupper('mst_'. $instance .'_'. $dataKey);

        return str_replace('-', '--', $key);
    }

    public function getCache(string $instance, string $dataKey): ?array
    {
        $cachedData = $this->cache->load($this->getCacheKey($instance, $dataKey));
        if (empty($cachedData)) {
            return null;
        } 
        $cachedData = $this->serializer->unserialize($cachedData);
        $cachedData = array_values($cachedData)[0];

        return is_array($cachedData)? $cachedData : [$cachedData];
    }

    public function setCache(string $instance, string $dataKey, array $dataValue): void
    {
        $this->cache->save($this->serializer->serialize($dataValue), $this->getCacheKey($instance, $dataKey));
    }
}
