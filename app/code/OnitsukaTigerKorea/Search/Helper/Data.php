<?php

namespace OnitsukaTigerKorea\Search\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_SKUS = 'search_custom/general/product_skus';
    const XML_PATH_ENABLED = 'search_custom/general/enabled';

    /**
     * Returns array of configured SKUs from admin config
     *
     * @return array
     */
    public function getConfiguredSkus(): array
    {
        $skuList = $this->scopeConfig->getValue(self::XML_PATH_SKUS, ScopeInterface::SCOPE_STORE);
        return array_filter(array_map('trim', explode(',', (string)$skuList)));
    }

    /**
     * Checks if the custom search feature is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }
}
