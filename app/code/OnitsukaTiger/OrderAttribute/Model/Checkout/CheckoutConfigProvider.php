<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Checkout;

use OnitsukaTiger\OrderAttribute\Model\ConfigProvider;
use Magento\Checkout\Model\ConfigProviderInterface;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider,
        private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $result = [];

        $result['otOrderAttribute']['sendOnShipping'] = $this->configProvider->isSendOnShipping();
        $result['currentCountryId'] = $this->scopeConfig->getValue('general/country/default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $result;
    }
}
