<?php
declare(strict_types=1);

namespace OnitsukaTiger\OrderStatus\Plugin;

use Cpss\Crm\Model\CpssApiRequest;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoresConfig;
use OnitsukaTiger\OrderStatus\Cron\CleanExpiredOrders;
use OnitsukaTiger\OrderStatus\Model\SourceDeduction\CancelOrderItem;

class CleanExpiredOrdersAdyenPayment
{
    /**
     * @var StoresConfig
     */
    protected $storesConfig;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderRepository
     */
    protected $order;

    private $orderManagement;

    /**
     * @var CancelOrderItem
     */
    private $cancelOrderItem;

    /**
     * CleanExpiredOrdersAdyenPayment constructor.
     * @param StoresConfig $storesConfig
     * @param CollectionFactory $collectionFactory
     * @param OrderRepository $order
     * @param CancelOrderItem $cancelOrderItem
     * @param OrderManagementInterface|null $orderManagement
     */
    public function __construct(
        StoresConfig $storesConfig,
        CollectionFactory $collectionFactory,
        OrderRepository $order,
        CancelOrderItem $cancelOrderItem,
        private CpssApiRequest $cpssApiRequest,
        OrderManagementInterface $orderManagement = null
    ) {
        $this->order = $order;
        $this->storesConfig = $storesConfig;
        $this->orderCollectionFactory = $collectionFactory;
        $this->cancelOrderItem = $cancelOrderItem;
        $this->orderManagement = $orderManagement ?: ObjectManager::getInstance()->get(OrderManagementInterface::class);
    }

    /**
     * @param CleanExpiredOrders $subject
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function beforeExecute(CleanExpiredOrders $subject)
    {
        $fileName = "AdyenCancelOrderLog-" . date('dd_mm_yyyy') . '.log';
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/' . $fileName);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('-----Adyen Cancel Order Log Start-----');

        $lifetimes = $this->storesConfig->getStoresConfigByPath('sales/orders/delete_pending_after');
        foreach ($lifetimes as $storeId => $lifetime) {
            /** @var $orders Collection */
            $orders = $this->orderCollectionFactory->create();
            $orders->addFieldToFilter('store_id', $storeId);
            $orders->addFieldToFilter('status', 'pending');

            $orders->getSelect()->join(
                ["payment" => "sales_order_payment"],
                'main_table.entity_id = payment.parent_id',
                ['method']
            )
                ->where('payment.method LIKE "%adyen%"')
                ->where(
                    new \Zend_Db_Expr('TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) >= ' . $lifetime * 60)
                );
            $logger->info("Order Collection Query : " . $orders->getSelect()->__toString());
            foreach ($orders->getAllIds() as $entityId) {
                $order = $this->order->get($entityId);
                $logger->info('Before order cancel');
                $logger->info("Order Updated At:" . $order->getUpdatedAt());
                $this->orderManagement->cancel((int)$entityId);
                $logger->info('After order cancel');
                $logger->info('Order ID: ' . $order->getId());
                if (!$order->isCanceled()) {
                    $logger->info('Cancel order item.');
                    foreach ($order->getAllItems() as $orderItem) {
                        $this->cancelOrderItem->execute($orderItem);
                    }
                }

                $logger->info('Set order status cancel before');
                $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
                $order->addStatusToHistory($order->getStatus(), 'Updated by Cron job');
                $usedPoint = $order->getUsedPoint();
                $usedPointRefunded = $order->getUsedPointRefunded();
                // check if credit has points to return, skip if none
                $logger->info('Used Points: ' . $usedPoint);
                $logger->info('Used Point Refunded: ' . $usedPointRefunded);
                if ($usedPoint > 0 && $usedPointRefunded <=0 && $order->hasInvoices()) {
                    $logger->info('Set Use Point Refunded: ' . $usedPoint);
                    $this->cpssApiRequest->addPoint(
                        $order->getIncrementId(),
                        $order->getCustomerId(),
                        $usedPoint
                    );
                    $order->setUsedPointRefunded($usedPoint);
                }
                $this->order->save($order);
                $logger->info('Set order status cancel After');
            }
        }
        $logger->info('-----Adyen Cancel Order Log End-----');
    }
}
