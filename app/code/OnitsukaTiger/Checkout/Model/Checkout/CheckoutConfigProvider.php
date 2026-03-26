<?php
/**
 * @author OnitsukaTiger Team
 * @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
 * @package Custom Checkout Fields for Magento 2
 */

namespace OnitsukaTiger\Checkout\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) { }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $result = [];
        $result['currentCountryId'] = $this->scopeConfig->getValue('general/country/default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $result;
    }
}
