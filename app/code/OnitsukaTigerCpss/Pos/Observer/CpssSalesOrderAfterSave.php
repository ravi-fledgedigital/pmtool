<?php

namespace OnitsukaTigerCpss\Pos\Observer;

use Cpss\Pos\Helper\CreateCsv;
use Cpss\Pos\Logger\Logger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\DirectoryList as DirectorySystem;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTigerCpss\Pos\Helper\HelperData;

class CpssSalesOrderAfterSave extends \Cpss\Pos\Observer\CpssSalesOrderAfterSave
{
    const DELIVERED = 'delivered';
    protected $helper;

    public function __construct(
        CreateCsv                $createCsv,
        Logger                   $logger,
        OrderRepositoryInterface $orderRepository,
        DirectorySystem          $directorySystem,
        ResourceConnection       $resourceConnection,
        HelperData               $helper
    ) {
        $this->helper = $helper;
        parent::__construct($createCsv, $logger, $orderRepository, $directorySystem, $resourceConnection);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $shipment = $observer->getEvent()->getShipment();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();

        if (!$this->helper->isEnableModule($order->getStoreId()) || $order->getCustomerIsGuest()) {
            return $this;
        }
        try {
            $completeOrders = [];
            /*$event = $observer->getEvent()->getShipment() ?? $observer->getEvent()->getInvoice();*/
            /*$invoiceSubtotal = 0;
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
            ) {*/

            $shipmentCount = count($order->getShipmentsCollection());
            for ($i = 1; $i <= $shipmentCount; $i++) {
                $orderIncrementId = $order->getIncrementId() . '_S' . $i;
                $completeOrders = $this->getCompletedOrders($orderIncrementId);
                if (empty($completeOrders)) {
                    break;
                }
            }

            /*$orderIncrementId = $order->getIncrementId() . '_S' . $shipmentCount;
            $completeOrders = $this->getCompletedOrders($orderIncrementId);*/
            if (empty($completeOrders)) {
                $currentOrderStatus = $order->getStatus();
                if (!in_array($currentOrderStatus, [parent::COMPLETE,parent::CLOSED,parent::PARTIAL_REFUND,self::DELIVERED])) {
                    $this->insertCompletedOrder($orderIncrementId);
                    $this->createCsv->generateEcData($order, null, null, $shipment, $orderIncrementId);
                    $this->createCsv->generateEcItemsData($order, $order->getIncrementId(), null, null, $shipment, $orderIncrementId);
                    $this->createCsv->generateEcProductData($order, $orderIncrementId);
                }
            }
            /*}*/
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this;
    }
}
