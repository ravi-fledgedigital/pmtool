<?php
namespace OnitsukaTigerKorea\Sales\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class GiftPackaging extends \Magento\Backend\Block\Template
{
    protected $registry;
    protected $orderRepository;
    protected $scopeConfig;

    public function __construct(
        Context $context,
        Registry $registry,
        OrderRepositoryInterface $orderRepository,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get current order
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    /**
     * Example: Get your custom order attribute
     */
    public function getGiftPackaging()
    {
        $order = $this->getOrder();
        $order->getStoreId();
        return $order ? $order->getData('gift_packaging') : null;
    }

    public function isGiftPackagingEnabled()
    {
        $order = $this->getOrder();
        return (bool) $this->getConfig('gift_packaging/gift_packaging/enable', $order->getStoreId());
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
}