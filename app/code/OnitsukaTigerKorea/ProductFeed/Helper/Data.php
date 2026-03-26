<?php

namespace OnitsukaTigerKorea\ProductFeed\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package OnitsukaTigerKorea\ProductFeed\Helper
 */
class Data extends AbstractHelper
{
    const ENABLE = 'productfeed_ot/general/enable';

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
    public function isProductFeedEnabled($storeId = null) {
        return (bool) $this->getConfig(self::ENABLE, $storeId);
    }
}
