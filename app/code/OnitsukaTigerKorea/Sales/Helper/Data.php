<?php

namespace OnitsukaTigerKorea\Sales\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Registry;

/**
 * Class Data
 * @package OnitsukaTigerKorea\Sales\Helper
 */
class Data extends AbstractHelper
{
    const ENABLE = 'korean_address/sales/enable';

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->registry = $registry;
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
    public function isSalesEnabled($storeId = null)
    {
        return (bool) $this->getConfig(self::ENABLE, $storeId);
    }

    /**
     * Check Partial Cancel Shipment
     *
     * @return bool
     */
    public function isPartialCancelShipment(): bool
    {
        return (bool)$this->registry->registry('is_partial_cancel');
    }

    /**
     * @param $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductBySku($sku)
    {
        return $this->productRepository->get($sku);
    }
}
