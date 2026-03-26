<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Model;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use OnitsukaTiger\Logger\StoreShipping\Logger;
use Magento\Store\Model\ScopeInterface;

/**
 * Class StoreShipping
 * @package OnitsukaTiger\NetSuiteStoreShipping\Model
 */
class StoreShipping
{
    const ADMIN_RESOURCE       = 'Magento_Sales::shipment';
    const STORE_SHIPPING_ROUTE = 'store_shipping';

    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param SourceRepositoryInterface $sourceRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     */
    public function __construct(
        SourceRepositoryInterface $sourceRepository,
        ScopeConfigInterface            $scopeConfig,
        Logger                    $logger
    )
    {
        $this->sourceRepository = $sourceRepository;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @param $sourceCode
     * @return bool
     */
    public function isShippingFromWareHouse($sourceCode)
    {
        return strpos($sourceCode, '_wh_');
    }

    /**
     * @param $sourceCode
     * @return SourceInterface|null
     */
    public function getSourcesDetails($sourceCode)
    {
        $sourceInfo = null;
        try {
            $sourceInfo = $this->sourceRepository->get($sourceCode);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $sourceInfo;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function enabledModuleStoreShipping($storeId = null){
        return $this->scopeConfig->getValue(
            'store_shipping/general/enabled', ScopeInterface::SCOPE_STORE, $storeId
        );
    }
}
