<?php

namespace OnitsukaTigerKorea\Shipping\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package OnitsukaTigerKorea\Sales\Helper
 */
class Data extends AbstractHelper
{
    const ENABLE = 'korean_address/sales/cancel_partially_order';
    const ENABLE_AUTO_CREDIT_MEMO = 'korean_address/sales/auto_creditmemo';

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
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return bool
     */
    public function isEnabled($storeId = null) {
        return (bool) $this->getConfig(self::ENABLE, $storeId);
    }

    public function enableAutoCreateCreditMemo($storeId = null) {
        return (bool) $this->getConfig(self::ENABLE_AUTO_CREDIT_MEMO, $storeId);
    }
}
