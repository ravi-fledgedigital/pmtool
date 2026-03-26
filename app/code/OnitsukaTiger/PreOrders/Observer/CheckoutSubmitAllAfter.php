<?php

namespace OnitsukaTiger\PreOrders\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use OnitsukaTiger\PreOrders\Helper\PreOrder;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CheckoutSubmitAllAfter implements ObserverInterface
{
    /**
    * @var \Magento\Sales\Api\OrderRepositoryInterface
    */
    protected $_orderRepository;

    /**
    * @var Order
    */
    protected $order;

    /**
    * @var PreOrder
    */
    protected $perOrderHelper;

    /**
    * @var ProductRepositoryInterface
    */
    protected $productRepositoryInterface;

    /**
    * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    * @param Order $order
    * @param PreOrder $perOrderHelper
    * @param ProductRepositoryInterface $productRepositoryInterface
    */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        Order $order,
        PreOrder $perOrderHelper,
        ProductRepositoryInterface $productRepositoryInterface,
        ) {
        $this->_orderRepository = $orderRepository;
        $this->order = $order;
        $this->perOrderHelper = $perOrderHelper;
        $this->productRepositoryInterface = $productRepositoryInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {   
        $orderId = $observer->getEvent()->getOrder()->getId();

        $order = $this->_orderRepository->get($orderId);

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/auto_invoice.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("set pre order status before invoice generate");

        $items = $order->getAllItems();

        try {
            foreach ($items as $item) {
                $productId = $item->getProductId();
                $isPreOrder = $this->perOrderHelper->isProductPreOrder($productId);
                $productRepo = $this->productRepositoryInterface->getById($productId);

                if ($isPreOrder) {
                    $order->setIsPreOrder($isPreOrder);
                    $order->save();

                    if($preOrderStartDate = $productRepo->getStartDatePreorder()){
                        $item->setLaunchDate($preOrderStartDate);
                        $item->save();
                    }
                }
            }
        } catch (\Exception $e) {
            $logger->info("Error - ".$e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }
}
