<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\QuickPurchase\Block;

use Magento\Catalog\Block\Product\View;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Button
 * @package OnitsukaTiger\QuickPurchase\Block
 */
class Button extends View
{
    const ENABLED_CONFIG_PATH = 'quick_purchase/show_button/enabled';
    const QUICK_PURCHASE_URL = 'quickpurchase/button/checkout';

    /**
     * Checks if button enabled.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isEnabled()
    {
        return $this->isModuleEnabled($this->getCurrentStoreId());
    }

    /**
     * @return string
     */
    public function getQuickPurchaseUrl()
    {
        return $this->getUrl(self::QUICK_PURCHASE_URL, ['_secure' => true, 'product_id' => $this->getProduct()->getId()]);
    }

    /**
     * Defines is feature enabled.
     *
     * @param int $storeId
     * @return bool
     */
    private function isModuleEnabled($storeId)
    {
        return $this->_scopeConfig->isSetFlag(
            self::ENABLED_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns active store view identifier.
     *
     * @return int
     */
    private function getCurrentStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
}
