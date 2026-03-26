<?php

namespace OnitsukaTiger\MegaMenu\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const IS_MODULE_ENABLED = 'category/mobile/module_enable';
    public const CATEGORY_IDS = 'category/mobile/category_ids';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * Get config value.
     *
     * @param mixed $path
     * @param mixed $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get current store.
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Is module enabled
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isModuleEnabled()
    {
        return $this->getConfigValue(self::IS_MODULE_ENABLED, $this->getStore()->getStoreId());
    }

    /**
     * Get category ids.
     *
     * @return string|string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategoryIds()
    {
        $categoryIds = $this->getConfigValue(self::CATEGORY_IDS, $this->getStore()->getStoreId());
        if (!empty($categoryIds)) {
            return explode(',', $categoryIds);
        }
        return '';
    }
}