<?php

namespace OnitsukaTigerKorea\Rma\Helper;

use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function enableShowProductSkuWms($storeId)
    {
        return $this->scopeConfig->getValue('onitsukatiger_catalog_product/product_sku_wms/enable',ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function syncRmaToSftp($storeId)
    {
        return $this->scopeConfig->getValue('onitsukatiger_catalog_product/rma_sftp/enable',ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getInitialStatusId($storeId)
    {
        return $this->scopeConfig->getValue('onitsukatiger_catalog_product/rma_status/initial_status',ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getInitialResolutionId($storeId)
    {
        return $this->scopeConfig->getValue('onitsukatiger_catalog_product/rma_status/rma_resolution',ScopeInterface::SCOPE_STORE, $storeId);
    }
}
