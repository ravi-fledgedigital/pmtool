<?php

namespace OnitsukaTigerKorea\Customer\Helper;

use Magento\Customer\Model\ResourceModel\Address\CollectionFactory as AddressCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Theme\ThemeProvider;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ENABLE = 'korean_address/customer/enable';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ThemeProvider
     */
    protected $themeProvider;

    protected $addressCollectionFactory;

    /**
     * Data constructor.
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ThemeProvider $themeProvider
     * @param Context $context
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ThemeProvider $themeProvider,
        AddressCollectionFactory $addressCollectionFactory,
        Context $context
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->themeProvider = $themeProvider;
        $this->addressCollectionFactory = $addressCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @param null $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isKoreanThemeEnable($storeId = null)
    {
        $themeId = $this->getThemeIdByConfig($storeId);
        $theme = $this->themeProvider->getThemeById($themeId);

        if ($theme->getCode() == 'Asics/onitsuka_korea') {
            return true;
        }

        return false;
    }

    /**
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getThemeIdByConfig($storeId = null)
    {
        if ($storeId == null) {
            return $this->scopeConfig->getValue(
                DesignInterface::XML_PATH_THEME_ID,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );
        }

        return $this->scopeConfig->getValue(
            DesignInterface::XML_PATH_THEME_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $customerId
     * @return \Magento\Customer\Model\ResourceModel\Address\Collection
     */
    public function getAddressFromCustomer($customerId) {
        $collection = $this->addressCollectionFactory->create();
        $collection->setOrder('entity_id', 'asc');
        $collection->setCustomerFilter([$customerId]);
        return $collection;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfig($path, $storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->getStoreId();
        }
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return bool
     */
    public function isCustomerEnabled($storeId = null) {
        return (bool) $this->getConfig(self::ENABLE, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function allowDeleteAccount($storeId = null): bool
    {
        return $this->getConfig('korean_address/customer_account/allow_delete_customer', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getDeleteAccountMessage($storeId = null): mixed
    {
        return $this->getConfig('korean_address/customer_account/delete_customer_message', $storeId);
    }
    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnabledKakao($storeId = null): mixed
    {
        return $this->getConfig('kakaosync/general/enable', $storeId);
    }
}
