<?php

namespace OnitsukaTiger\MaxQty\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const IS_MODULE_ENABLED = 'maxqtyconfiguration/maxqtyconfig/enable';
    const MAX_QTY = 'maxqtyconfiguration/maxqtyconfig/max_qty';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * Get config value.
     *
     * @param $path
     * @param $storeId
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
     * Is module enabled?
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isModuleEnabled()
    {
        return $this->getConfigValue(self::IS_MODULE_ENABLED, $this->getStore()->getStoreId());
    }

    /**
     * Get allowed max qty.
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMaxAllowedQty()
    {
        $maxQty = $this->getConfigValue(self::MAX_QTY, $this->getStore()->getStoreId());
        return (!empty($maxQty)) ? $maxQty: 0;
    }
}
