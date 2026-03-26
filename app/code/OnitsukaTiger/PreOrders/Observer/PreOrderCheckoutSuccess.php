<?php
/** phpcs:ignoreFile */
namespace OnitsukaTiger\PreOrders\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use OnitsukaTiger\PreOrders\Helper\PreOrder;

class PreOrderCheckoutSuccess implements ObserverInterface
{
    /**
     * @var $order;
     */
    protected $order;

    /**
     * @var Stock
     */
    protected $stock;

    /**
     * @var PreOrder
     */
    protected $perOrderHelper;

    /**
     * PreOrderCheckoutSuccess constructor.
     *
     * @param Order $order
     * @param PreOrder $perOrderHelper
     */
    public function __construct(Order $order, PreOrder $perOrderHelper)
    {
        $this->order = $order;
        $this->perOrderHelper = $perOrderHelper;
    }

    /**
     * Order status should be Pre Order Pending when someone place the order of pre order items
     * @param Observer $observer
     *
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        $order = $this->order->load($orderIds);
        $items = $order->getAllItems();
        foreach ($items as $item) {
            $productId = $item->getProductId();
            $isPreOrder = $this->perOrderHelper->isProductPreOrder($productId);
            if ($isPreOrder) {
                $order->setState("new")->setStatus("pre_order_pending");
                $order->setIsPreOrder($isPreOrder);
                $order->save();
            }
        }
    }
}
