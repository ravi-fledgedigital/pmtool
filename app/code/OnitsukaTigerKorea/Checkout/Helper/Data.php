<?php

namespace OnitsukaTigerKorea\Checkout\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package OnitsukaTigerKorea\Checkout\Helper
 */
class Data extends AbstractHelper
{
    const ENABLE = 'korean_address/checkout/enable';

    const GIFT_PACKAGING = 'gift_packaging/gift_packaging/enable';

    const SKU_PREFIX = 'gift_packaging/gift_packaging/prefix_of_skus';

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
    public function isCheckoutEnabled($storeId = null) {
        return (bool) $this->getConfig(self::ENABLE, $storeId);
    }

    /**
     * Get current store code
     *
     * @return  int
     */
    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isGiftPackagingEnabled($storeId = null)
    {
        return (bool) $this->getConfig(self::GIFT_PACKAGING, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function skuPrefix($storeId = null)
    {
        return $this->getConfig(self::SKU_PREFIX, $storeId);
    }
}
