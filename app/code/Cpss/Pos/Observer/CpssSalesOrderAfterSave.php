<?php

namespace Cpss\Pos\Observer;

use Magento\Framework\Event\ObserverInterface;
use Cpss\Pos\Helper\CreateCsv;
use Cpss\Pos\Logger\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Filesystem\DirectoryList as DirectorySystem;
use Magento\Framework\App\ResourceConnection;
class CpssSalesOrderAfterSave implements ObserverInterface
{
    //Order Status
    const PENDING = 'pending';
    const PROCESSING = 'processing';
    const COMPLETE = 'complete';
    const CLOSED = 'closed';

    public const PARTIAL_REFUND = 'partial_refund';
    public const CPSS_DIR = 'cpss/';
    public const COMPLETED_ORDERS_FILENAME = 'completedOrders.csv';
    public const CPSS_COMPLETED_ORDERS_TABLE = 'cpss_completed_orders';
    protected $createCsv;
    protected $logger;
    protected $orderRepository;
    protected $directorySystem;
    protected $resourceConnection;

    public function __construct(
        CreateCsv $createCsv,
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        DirectorySystem $directorySystem,
        ResourceConnection $resourceConnection
    ) {
        $this->createCsv = $createCsv;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->directorySystem = $directorySystem;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $completeOrders = [];
            $event = $observer->getEvent()->getShipment() ?? $observer->getEvent()->getInvoice();
            /** @var \Magento\Sales\Model\Order $order */
            $order = $event->getOrder();
            $invoiceSubtotal = 0;
            $invoices = $order->getInvoiceCollection();
            foreach($invoices as $invoice){
                $invoiceSubtotal += $invoice->getSubtotal();
            }

            $shipmentTotalQty = 0;
            $shipments = $order->getShipmentsCollection();
            foreach($shipments as $shipment){
                $shipmentTotalQty += $shipment->getTotalQty();
            }

            $completeOrders = $this->getCompletedOrders($order->getIncrementId());
            if (empty($completeOrders) &&
                $order->getCustomerId() &&
                $order->getSubtotal() == $invoiceSubtotal &&
                (int)$order->getTotalQtyOrdered() == (int)$shipmentTotalQty
            ) {
                $currentOrderStatus = $order->getStatus();
                if($currentOrderStatus != self::COMPLETE &&
                    $currentOrderStatus != self::CLOSED &&
                    $currentOrderStatus != self::PARTIAL_REFUND
                ) {
                    $this->insertCompletedOrder($order->getIncrementId());
                    $this->logger->info("Record Order to CPSS csv.", [$order->getIncrementId()]);
                    $this->createCsv->generateEcData($order);
                    $this->createCsv->generateEcItemsData($order, $order->getIncrementId());
                    $this->createCsv->generateEcProductData($order);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this;
    }

    public function insertCompletedOrder($incrementId)
    {
        $connection= $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::CPSS_COMPLETED_ORDERS_TABLE);
        $sql = "INSERT INTO " . $table . " (increment_id) VALUES ('" . $incrementId . "')";
        $connection->query($sql);
    }
    public function getCompletedOrders($incrementId)
    {
        $connection= $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::CPSS_COMPLETED_ORDERS_TABLE);
        $sql = "SELECT increment_id FROM " . $table . " WHERE increment_id = '". $incrementId . "'";
        return $connection->fetchCol($sql);
    }
}
