<?php

namespace Cpss\Crm\Observer\Admin;

use Cpss\Crm\Helper\Data;
use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\CpssApiRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\CollectionFactory;

class CreditMemoPoint implements ObserverInterface
{
    protected $pointHelper;
    protected $orderRepository;
    protected $cpssApiRequest;
    protected $collection;
    protected $request;
    protected $orderItemRepository;
    protected $logger;

    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\HelperData
     */
    protected $helper;

    /**
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    protected $orderItemFactory;

    public function __construct(
        Data $pointHelper,
        CpssApiRequest $cpssApiRequest,
        CollectionFactory $collection,
        RequestInterface $request,
        OrderItemRepositoryInterface $orderItemRepository,
        Logger $logger,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $helper,
        \Magento\Sales\Model\Order\ItemFactory $orderItemFactory
    ) {
        $this->pointHelper = $pointHelper;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->collection = $collection;
        $this->request = $request;
        $this->orderItemRepository = $orderItemRepository;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->orderItemFactory = $orderItemFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->pointHelper->enabled()) {
            return $this;
        }

        $creditMemo = $observer->getEvent()->getCreditmemo();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $creditMemo->getOrder();

        // check if order used points, skip if not
        $orderUsedPoint = $order->getUsedPoint();
        if (empty($orderUsedPoint) || $orderUsedPoint <= 0) {
            return $this;
        }

        $refundItemDetails = [];

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create(\OnitsukaTigerCpss\Crm\Helper\HelperData::class);

        $totalUsedPoints = 0;

        foreach ($creditMemo->getAllItems() as $item) {
            if (!empty($item->getDiscountAmount())) {
                $orderItem = $this->orderItemFactory->create()->load($item->getOrderItemId());
                $orderQty = $orderItem->getQtyOrdered();
                $returnQty = $item->getQty();

                $usedPoint = $orderItem->getUsedPoint();
                if ($orderQty != $returnQty) {
                    $usedPoint = ($orderItem->getUsedPoint() / $orderQty) * $returnQty;
                }
                $refundItemDetails[$item->getSku()] = $usedPoint;
                $totalUsedPoints += $usedPoint;
            }
        }

        // check if credit has points to return, skip if none
        if ($totalUsedPoints <= 0) {
            return $this;
        } else {
            $perPoint = (!empty($this->helper->getPerXPointValue($order->getStoreId()))) ? $this->helper->getPerXPointValue($order->getStoreId()) : $this->helper->getPointEarnedMultiplyBy($order->getStoreId());
            $totalUsedPoints = $totalUsedPoints * $perPoint;
        }

        /*$pointUsed = abs($creditMemo->getDiscountAmount());*/
        $customerId = $order->getCustomerId();
        $count = $this->getRefundTimes($order->getId());
        $totalPointsRefunded = $order->getUsedPointRefunded() + $totalUsedPoints;
        if ($order->getState() != "closed" || $order->getState() == 'partial_refund') {
            if ($order->getUsedPointRefunded() == $totalUsedPoints) {
                return $this;
            }
            $refundTimes = $count + 1;
            $totalUsedPoints = round($totalUsedPoints);
            $apiResult = $this->cpssApiRequest->addPoint($order->getIncrementId(), $customerId, $totalUsedPoints, $refundTimes);
        } else {
            if ($order->getUsedPointRefunded() > 0) {
                return $this;
            }
            $apiResult = $this->cpssApiRequest->addPoint($order->getIncrementId(), $customerId, $totalUsedPoints);
        }

        $order->setCpssRefundSubStatus($apiResult['X-CPSS-Result']);
        $order->setUsedPointRefunded($totalPointsRefunded);

        try {
            if ($apiResult['X-CPSS-Result'] == "000-000-000") {
                foreach ($order->getAllVisibleItems() as $item) {
                    if (!isset($refundItemDetails[$item->getSku()])) {
                        continue;
                    }

                    try {
                        $item->setRefundedUsedPoint($item->getRefundedUsedPoint() + $refundItemDetails[$item->getSku()]);
                        $item->save();
                    } catch (\Exception $e) {
                        $this->logger->critical($e->getMessage());
                    }
                }

                $order->save();
            } else {
                //Log response if API error occured
                $this->logger->error('cpssApiRequest: ' . $apiResult['X-CPSS-Result']);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    public function getRefundTimes($orderId)
    {
        $collection = $this->collection->create();
        $collection->addFieldToFilter('order_id', ['eq' => $orderId]);

        return count($collection->getData());
    }
}

