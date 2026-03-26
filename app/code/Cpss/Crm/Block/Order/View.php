<?php
namespace Cpss\Crm\Block\Order;

class View extends \Magento\Framework\View\Element\Template
{
    protected $shopReceipt;
    protected $realStoreFactory;
    protected $sessionManager;
    protected $http;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Cpss\Crm\Model\ShopReceipt $shopReceipt,
        \Cpss\Crm\Model\RealStoreFactory  $realStoreFactory,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\App\Response\Http $http,
        array $data = []
    ) {
        $this->shopReceipt = $shopReceipt;
        $this->realStoreFactory = $realStoreFactory;
        $this->sessionManager = $sessionManager;
        $this->http = $http;
        parent::__construct($context, $data);
    }

    /**
     * Get real store name by shop_id
     *
     * @return string
     */
    public function getStoreName($shopId) {
        $stores = $this->sessionManager->getRealStores();
        $storeName = isset($stores[$shopId]) ? $stores[$shopId] : __('Real Store not Registered');
        return $storeName;
    }

    /**
     * Get real store order by purchase_id
     *
     * @return object
     */
    public function getOrder() {
        $purchaseId = $this->getRequest()->getParam('purchase_id');
        $order = $this->shopReceipt->loadByPurchaseId($purchaseId);
        return (!empty($order->getData())) ? $order : null;
    }

    /**
     * Redirects to a specified page.
     */
    public function redirectToSpecificPage($destination) {
        return $this->http->setRedirect( $this->getBaseUrl() . $destination );
    }

    /**
     * Retrieve formatting date
     *
     * @param null|string|\DateTimeInterface $date
     * @param int $format
     * @param bool $showTime
     * @param null|string $timezone
     * @return string
     */
    public function formatDate(
        $date = null,
        $format = \IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null
    ) {
        $date = $date instanceof \DateTimeInterface ? $date : new \DateTime($date ?? 'now');
        return $this->_localeDate->formatDateTime(
            $date,
            $format,
            $showTime ? $format : \IntlDateFormatter::NONE,
            null,
            $timezone
        );
    }

    public function getShopName($purchaseId, $storeCode = null)
    {
        preg_match("/^([0-9]{8})_([a-zA-Z0-9]{6})_([0-9]{5})_([0-9]{10})$/", $purchaseId, $extractedData);
        if ($storeCode == 'kr') {
            preg_match("/^([0-9]{8})_([a-zA-Z0-9]{8})_([0-9]{5})_([0-9]{10})$/", $purchaseId, $extractedData);
        }
        $shopId = $extractedData[2];
        $realStore = $this->realStoreFactory->create();
        $realStore->loadById($shopId);
        return $realStore->getShopName() ?? "";
    }

    public function getBackUrl()
    {
        return $this->getUrl('sales/order/history');
    }
}
