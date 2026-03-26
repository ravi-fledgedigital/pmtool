<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Model;

class ConfigProvider
{
    const AITOC_SENDGRID_API_SECRET = 'sendgrid/general/api_secret';
    const AITOC_SENDGRID_ENABLED = 'sendgrid/general/enabled';
    const AITOC_SENDGRID_SUBSCRIBE_LIST = 'sendgrid/general/subscribe_list';
    const AITOC_SENDGRID_UNSUBSCRIBE_LIST = 'sendgrid/general/unsubscribe_list';
    const AITOC_SENDGRID_SYNC_CRON = 'sendgrid/sync/cron';
    const AITOC_SENDGRID_WITHOUT_SUB = 'sendgrid/general/without_sub';
    const AITOC_SENDGRID_LIST_FOR_NEW_CUSTOMER = 'sendgrid/general/list_for_new';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isEnabled($storeId)
    {
        return $this->isSetFlag(self::AITOC_SENDGRID_ENABLED, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getApiKey($storeId)
    {
        return $this->getConfigValue(self::AITOC_SENDGRID_API_SECRET, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSubscribeListId($storeId)
    {
        return $this->getConfigValue(self::AITOC_SENDGRID_SUBSCRIBE_LIST, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUnsubscribeListId($storeId)
    {
        return $this->getConfigValue(self::AITOC_SENDGRID_UNSUBSCRIBE_LIST, $storeId);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isCronEnabled()
    {
        return $this->isSetFlag(self::AITOC_SENDGRID_SYNC_CRON, $this->storeManager->getStore()->getStoreId());
    }


    /**
     * @param $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isAddCustomerWithoutSubscribe($storeId)
    {
        return $this->isSetFlag(self::AITOC_SENDGRID_WITHOUT_SUB, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getListForNewCustomer($storeId = null)
    {
        return $this->getConfigValue(self::AITOC_SENDGRID_LIST_FOR_NEW_CUSTOMER, $storeId);
    }

    /**
     * @param $path
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfigValue($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId ?: $this->storeManager->getStore()->getStoreId()
        );
    }

    /**
     * @param $path
     * @param null $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isSetFlag($path, $storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId ?: $this->storeManager->getStore()->getStoreId()
        );
    }
}
