<?php

namespace OnitsukaTigerKorea\RmaAddress\Helper;

use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTigerKorea\RmaAddress\Model\ResourceModel\RmaRequestAddress\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

use Magento\Framework\App\Helper\Context;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{


    /**
     * @var AddressConfig
     */
    protected $addressConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;


    public function __construct(
        CollectionFactory $collectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        AddressConfig $addressConfig,
        Context $context
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->addressConfig = $addressConfig;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getRmaAddress($request)
    {
        $requestId = $request->getRequestId();
        $addressCollection = $this->collectionFactory->create()
            ->addFieldToFilter('rma_request_id', $requestId);
        return $addressCollection->getFirstItem();
    }

    public function rmaAddressToHtml($request)
    {
        $address = $this->getRmaAddress($request);
        if (!$address->getId()) {
            return '';
        }
        return $this->format($address, 'html', $request->getStoreId());
    }

    /**
     * @param $address
     * @param $type
     * @param $storeId
     * @return |null
     */
    public function format($address, $type, $storeId)
    {
        $this->addressConfig->setStore($storeId);
        $formatType = $this->addressConfig->getFormatByCode($type);
        if (!$formatType || !$formatType->getRenderer()) {
            return null;
        }
        return $formatType->getRenderer()->renderArray($address->getData());
    }

    /**
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function enableShowAddressRMA($storeId = null)
    {
        if ($storeId == null) {
            return $this->scopeConfig->getValue('onitsukatiger_catalog_product/rma_address/enable',ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId());
        }

        return $this->scopeConfig->getValue('onitsukatiger_catalog_product/rma_address/enable',ScopeInterface::SCOPE_STORE, $storeId);
    }
}
