<?php

namespace OnitsukaTiger\FixLocale\Plugin;

use Magento\Framework\Currency;
use Magento\Framework\App\CacheInterface;

class CurrencyPlugin
{
    public function beforeConstruct(
        Currency $subject,
        CacheInterface $appCache,
        $options = null,
        $locale = null
    ) {
        if ($locale === 'en_MY') {
            $locale = 'en_US';
        }

        return [$appCache, $options, $locale];
    }
}
