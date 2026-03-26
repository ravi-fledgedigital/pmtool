<?php

namespace Vaimo\AepBase\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const IS_ENABLED_FOR_STORE = 'aep/general/enabled_for_store';
    const EXCLUDE_STORE_PRODUCTS = 'aep/general/exclude_store_products';
    const EXCLUDE_WEBSITE_CUSTOMER = 'aep/general/exclude_website_customer';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * @param $path
     * @param $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId): mixed
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return mixed
     */
    public function getExcludeStoreProducts(): mixed
    {
        return $this->getConfigValue(self::EXCLUDE_STORE_PRODUCTS, $this->storeManager->getStore()->getStoreId());
    }

    /**
     * @param $storeId
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreById($storeId)
    {
        return $this->storeManager->getStore($storeId)->getCurrentCurrencyCode();
    }

    /**
     * @return mixed
     */
    public function getExcludeWebsiteCustomer(): mixed
    {
        return $this->getConfigValue(self::EXCLUDE_WEBSITE_CUSTOMER, $this->storeManager->getStore()->getStoreId());
    }
}
