<?php

namespace OnitsukaTiger\Rma\Helper;

use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
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


    protected $statusTrack;

    protected $configurable;

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
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->configProvider = $configProvider;
        $this->helperTrack = $helperTrack;
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
        $this->request = $request;
        $this->configurable = $configurable;
        $this->statusTrack = $this->scopeConfig->getValue('amrma/general/status_expiry', ScopeInterface::SCOPE_STORE);
        parent::__construct($context);
    }

    /**
     * @param $product
     * @param $superAttribute
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getChildFromProductAttribute($product,$superAttribute) {
        $usedChild = $this->configurable->getProductByAttributes($superAttribute ,$product);
        return $usedChild;
    }

    /**
     * @param $order
     * @return bool
     */
    public function checkButtonReorder($order) {
        if ($this->configProvider->isEnabled()) {
            $allowedStatuses = $this->configProvider->getAllowedOrderStatuses();
            if (empty($allowedStatuses) || in_array($order->getStatus(), $allowedStatuses)) {
                $now = time(); // or your date as well
                if($this->getStatusTrack($order)){
                    $orderDate = strtotime($this->getStatusTrack($order));
                    $datediff = $now - $orderDate;
                    $datediff = $datediff / (60 * 60 * 24);
                    if($datediff <= (int)$this->scopeConfig->getValue('amrma/general/date_expriry', ScopeInterface::SCOPE_STORE)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getStatusTrack($order){
        $statusTrackDate = $this->helperTrack->getStatusTrackingDate($order,$this->statusTrack);
        if($statusTrackDate){
            return $statusTrackDate->getFirstItem()->getCreatedAt();
        }else{
            $statusTrackDate = $this->helperTrack->getStatusTrackingDate($order->getId(),'complete');
            if($statusTrackDate->getFirstItem()){
                return $statusTrackDate->getFirstItem()->getCreatedAt();
            }
        }
        return $order->getCreatedAt();
    }

    /**
     * @param $order
     * @param null $item
     * @return string
     */
    public function getCreateReturnUrl($order,$item = null)
    {
        if($item){
            return $this->_urlBuilder->getUrl(
                $this->configProvider->getUrlPrefix() . '/account/newreturn',
                [
                    'order' => $order->getId(),
                    'item' => $item->getSku()
                ]
            );
        }
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/account/newreturn',
            ['order' => $order->getId()]
        );
    }
    public function showItem($item = null){
        if($this->request->getParam('item')) {
            if($this->request->getParam('item') == $item->getSku()){
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Catalog\Model\Product
     */
    public function getFinalProductThumbnail($item, $order)
    {
        $productThumbnail = $item->getProduct();
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getParentItemId() == $item->getId()) {
                return $orderItem->getProduct();
            }
        }
        return $productThumbnail;
    }

    /**
     * @param $path
     * @param $storeId
     * @return mixed
     */
    public function getRmaEmailTemplateConfig($path, $storeId) {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getRmaToCreditMemoConfig($storeId) {
        return $this->scopeConfig->getValue('rma_to_creditmemo/general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeID
     * @return mixed
     */
    public function getIsShowSyncButton($storeID): mixed
    {
        return $this->scopeConfig->getValue(
            'amrma/general/is_show_button_sync',
            ScopeInterface::SCOPE_STORE,
            $storeID
        );
    }
}
