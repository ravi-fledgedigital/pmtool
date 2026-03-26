<?php

namespace Vaimo\AepEventStreaming\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{ 
    const EXCLUDE_STORE_STREAMING = 'aep_event_streaming/general/exclude_store_aep_streaming';
    const EXCLUDE_WEBSITE_STREAMING = 'aep_event_streaming/general/exclude_website_aep_streaming';

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
    public function getExcludeStoreStreaming(): mixed
    {
        return $this->getConfigValue(self::EXCLUDE_STORE_STREAMING, $this->storeManager->getStore()->getStoreId());
    }

    /**
     * @return mixed
     */
    public function getExcludeWebsiteStreaming(): mixed
    {
        return $this->getConfigValue(self::EXCLUDE_WEBSITE_STREAMING, $this->storeManager->getStore()->getStoreId());
    }

    /**
     * get store code by id
     * @return string
     */
    public function getStoreCodeById($storeId)
    {
        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (NoSuchEntityException $e) {
            return $e->getMessage();
        }

        return $store->getCode();
    }
}
