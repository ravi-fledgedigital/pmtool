<?php

namespace OnitsukaTiger\Rma\Helper;

use Amasty\Rma\Model\ConfigProvider;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use OnitsukaTiger\OrderStatusTracking\Helper\Data as HelperTrack;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 */
class OrderStatusHistory extends Data
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var Http
     */
    protected $request;

    public function __construct(
        ConfigProvider              $configProvider,
        Http                        $request,
        ScopeConfigInterface        $scopeConfig,
        HelperTrack                 $helperTrack,
        Configurable                $configurable,
        Registry                    $registry,
        StoreManagerInterface       $storeManager,
        CustomerRepositoryInterface $customerRepository,
        Context                     $context)
    {
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->request = $request;


        parent::__construct(
            $configProvider,
            $request,
            $scopeConfig,
            $helperTrack,
            $configurable,
            $registry,
            $context);
    }


    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIsShowHistoryOfMemo()
    {
        return $this->scopeConfig->getValue('amrma/general/is_show_history_of_memo',
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId());
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIsShowHistoryOfRma()
    {

        return $this->scopeConfig->getValue('amrma/general/is_show_history_of_rma',
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId());
    }

    /**
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStoreId()
    {
        $customer = $this->customerRepository->getById($this->request->getParam('id'));
        return $customer->getStoreId();

    }
}
