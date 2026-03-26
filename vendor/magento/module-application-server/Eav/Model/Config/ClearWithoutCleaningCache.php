<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\Eav\Model\Config;

use Magento\Eav\Model\Config;
use Magento\Framework\App\Cache\Type\Dummy as DummyCache;
use Magento\Framework\App\ObjectManager;

/**
 * Decorator for EAV config model that adds clearWithoutCleaningCache
 */
class ClearWithoutCleaningCache extends Config
{
    /**
     * Clear EAV config cache without cleaning cache
     *
     * @return void
     */
    public function clearWithoutCleaningCache()
    {
        $actualCache = $this->_cache;
        $this->_cache = ObjectManager::getInstance()->get(DummyCache::class);
        parent::clear();
        $this->_cache = $actualCache;
    }
}
