<?php

namespace OnitsukaTiger\Rma\Helper;

use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\OptionSource\State;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 */
class DataCancelReturn extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \OnitsukaTiger\OrderStatusTracking\Helper\Data
     */
    protected $helperTrack;



    protected $configurable;

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * Data constructor.
     * @param ConfigProvider $configProvider
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \OnitsukaTiger\OrderStatusTracking\Helper\Data $helperTrack
     * @param Registry $registry
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        ConfigProvider $configProvider,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \OnitsukaTiger\OrderStatusTracking\Helper\Data $helperTrack,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        Registry $registry,
        StatusRepositoryInterface $statusRepository,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->configProvider = $configProvider;
        $this->helperTrack = $helperTrack;
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
        $this->request = $request;
        $this->configurable = $configurable;
        $this->statusRepository = $statusRepository;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param $statusId
     *
     * @return \Amasty\Rma\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStatusModel($statusId)
    {
        return $this->statusRepository->getById($statusId, $this->_storeManager->getStore()->getId());
    }

    /**
     * @param $order
     * @param $requestCancel
     * @return bool
     */
    public function validCancelStatus($order, $requestCancel)
    {
        $status = $this->getStatusModel($requestCancel->getStatus());

        if($status->getState() == State::CANCELED){
            return false;
        }

        if($order->getStatus() == \OnitsukaTiger\OrderStatus\Model\OrderStatus::STATUS_DELIVERED && $order->getCancelXmlSynced() && $order->canCreditmemo()) {
            return true;
        }

        if($order->hasCreditmemos()){
            return false;
        }

        return true;

    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIsShowAdminCanceledStatusConfig($storeID)
    {
        return $this->scopeConfig->getValue('amrma/general/is_show_admin_canceled_status',
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIsShowCustomerCanceledStatusConfig($storeID)
    {
        return $this->scopeConfig->getValue('amrma/general/is_show_frontend_canceled_status',
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }
}
