<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\OrderStatus\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\Logger\Logger;
use OnitsukaTiger\OrderStatus\Model\SourceDeduction\CancelOrderItem;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoresConfig;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

/**
 * Class that provides functionality of cleaning expired quotes by cron
 */
class CleanExpiredOrders
{
    /**
     * @var CancelOrderItem
     */
    protected $cancelOrderItem;

    /**
     * @var StoresConfig
     */
    protected $storesConfig;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var OrderRepository
     */
    protected $_order;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CancelOrderItem $cancelOrderItem
     * @param StoresConfig $storesConfig
     * @param CollectionFactory $collectionFactory
     * @param OrderRepository $order
     * @param Json $json
     * @param Logger $logger
     * @param OrderManagementInterface|null $orderManagement
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CancelOrderItem $cancelOrderItem,
        StoresConfig $storesConfig,
        CollectionFactory $collectionFactory,
        OrderRepository $order,
        Json $json,
        Logger $logger,
        OrderManagementInterface $orderManagement = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cancelOrderItem = $cancelOrderItem;
        $this->_order = $order;
        $this->storesConfig = $storesConfig;
        $this->orderCollectionFactory = $collectionFactory;
        $this->json = $json;
        $this->logger = $logger;
        $this->orderManagement = $orderManagement ?: ObjectManager::getInstance()->get(OrderManagementInterface::class);
    }

    /**
     * Clean expired quotes (cron process)
     *
     * @return void
     */
    public function execute()
    {
        $lifetimes = $this->storesConfig->getStoresConfigByPath('sales/orders/delete_pending_after');

        $this->customlogger()->info('========= auto cancel order logger start ===========');

        foreach ($lifetimes as $storeId => $lifetime) {
            $paymentMethods = $this->getPaymentMethodsApply($storeId);
            if (!$storeId || empty($paymentMethods)) {
                continue;
            }

            $orders = $this->getOrderCollection($storeId, $lifetime);
            if ($paymentMethodQuery = $this->getPaymentMethodQuery($paymentMethods)) {
                $orders->getSelect()->where($paymentMethodQuery);
            } else {
                continue;
            }

            try {
                $this->cancelOrders($orders);
            } catch (\Exception $e) {
                $this->customlogger()->info('catch error message - '.$e->getMessage());
                $this->logger->error($e->getMessage(), $e->getTrace());
                continue;
            }
        }

        $this->customlogger()->info('========= auto cancel order logger end ===========');
    }

    /**
     * @param $storeId
     * @return array
     */
    private function getPaymentMethodsApply($storeId)
    {
        $value = $this->scopeConfig->getValue(
            'sales/orders/payment_mapping',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value) {
            return $this->json->unserialize($value);
        }

        return [];
    }

    /**
     * @param $paymentMethods
     * @return string
     */
    private function getPaymentMethodQuery($paymentMethods)
    {
        $methodSize = count($paymentMethods);
        $count = 0;
        $paymentMethodQuery = '';

        foreach ($paymentMethods as $method) {
            $count++;
            if (empty($method['statuses'])) {
                continue;
            }
            $statuses = implode("','", array_values($method['statuses']));
            $paymentMethodQuery .= "(payment.method ='{$method['method']}' AND main_table.status IN ('{$statuses}'))";
            if ($methodSize > $count) {
                $paymentMethodQuery .= " OR ";
            }
        }

        return $paymentMethodQuery;
    }

    /**
     * @param int $storeId
     * @param $lifetime
     * @return Collection
     */
    private function getOrderCollection(int $storeId, $lifetime)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/Clean-Expired-Orders.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $orders = $this->orderCollectionFactory->create();
        $orders->addFieldToFilter('store_id', $storeId);

        $orders->getSelect()->join(
            ["payment" => "sales_order_payment"],
            'main_table.entity_id = payment.parent_id',
            array('method')
        )->where(
            new \Zend_Db_Expr('TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) >= ' . $lifetime * 60)
        );
        $logger->info("Order collection query : " . $orders->getSelect()->__toString());
        return $orders;
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws InputException
     */
    private function cancelOrders(Collection $orders)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/Clean-Expired-Orders.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        foreach ($orders->getAllIds() as $entityId) {
            $order = $this->_order->get($entityId);
            $logger->info("Order Updated At:" . $order->getUpdatedAt());
            $this->orderManagement->cancel((int) $entityId);
            $this->customlogger()->info($order->getIncrementId(). ' order canceled : '. $order->isCanceled());
            if (!$order->isCanceled()) {
                foreach ($order->getAllItems() as $orderItem) {
                    $this->cancelOrderItem->execute($orderItem);
                }
            }

            $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
            $order->addStatusToHistory($order->getStatus(), 'Updated by Cron job');

            $this->customlogger()->info($order->getIncrementId().' order status : '. $order->getStatus());

            $this->_order->save($order);
        }
    }

    /**
     * @return \Zend_Log
     * @throws \Zend_Log_Exception
     */
    private function customLogger()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/auto_canceled_order.log');
        $customlogger = new \Zend_Log();
        $customlogger->addWriter($writer);

        return $customlogger;
    }
}