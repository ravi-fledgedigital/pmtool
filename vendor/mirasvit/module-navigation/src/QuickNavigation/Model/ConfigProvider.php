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

namespace Mirasvit\QuickNavigation\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider
{
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('mst_quick_navigation/general/is_enabled', ScopeInterface::SCOPE_STORE);
    }

    public function getTotalThreshold(): int
    {
        return (int)$this->scopeConfig->getValue('mst_quick_navigation/general/total_threshold', ScopeInterface::SCOPE_STORE);
    }

    public function getAttributeThreshold(): int
    {
        return (int)$this->scopeConfig->getValue('mst_quick_navigation/general/attribute_threshold', ScopeInterface::SCOPE_STORE);
    }

    public function isCleanupEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag('mst_quick_navigation/general/is_cleanup');
    }
}
