<?php

namespace OnitsukaTiger\Sales\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class UpdateBlankQtyInvoicedOrder
{
    /**
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private OrderFactory         $orderFactory,
        private ScopeConfigInterface $scopeConfig,
        private StoreManagerInterface $storeManager,
        private CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron_update_invoice_qty.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("----------------Logger Started--------------");
        $hours = $this->getConfigHours();
        $logger->info("Config Hours : " . $hours);
        $seconds = $hours * 3600;
        $fromTime = date("Y-m-d H:i:s", (time() - $seconds));
        $logger->info("From time : " . $fromTime);
        $orderStatus = $this->getOrderStatus();
        $logger->info('Order Status : ' . print_r(json_decode(json_encode($orderStatus)), true));
        $storeIds = $this->isEnabledForStore();
        $logger->info('Enabled For Store : ' . print_r(json_decode(json_encode($storeIds)), true));
        $orderIds = [];
        $orderCollection = $this->collectionFactory->create()
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('status', ['in' => $orderStatus])
            ->addFieldToFilter('created_at', [
                'from'     => $fromTime,
                'to'       => time(),
                'datetime' => true
            ]);
        $logger->info("Total order : " . $orderCollection->getSize());
        if ($orderCollection->getSize() > 0) {
            foreach ($orderCollection as $order) {
                if ($order->hasInvoices()) {
                    $orderIds[] = $order->getId();
                }
            }
        }
        $this->updateOrderItem($orderIds);
    }

    /**
     * @param $orderIds
     * @return void
     * @throws \Exception
     */
    public function updateOrderItem($orderIds)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron_update_invoice_qty.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $order = $this->orderFactory->create()->load($orderId);
                $orderItems = $order->getItems();
                $flag = 0;
                foreach ($orderItems as $orderItem) {
                    if ((int)$orderItem->getQtyInvoiced() == 0) {
                        $orderItem->setQtyInvoiced($orderItem->getQtyOrdered());
                        $orderItem->setRowInvoiced($orderItem->getRowTotal());
                        $orderItem->setBaseRowInvoiced($orderItem->getBaseRowTotal());
                        $orderItem->save();
                        $flag = 1;
                        $logger->info("Updated Order #{$order->getIncrementId()} Item ID {$orderItem->getId()}");
                    }
                }
                if (empty($order->getTotalPaid()) || $order->getTotalPaid() == "0.0000") {
                    $order->setTotalPaid($order->getGrandTotal());
                    $logger->info("Total Paid #{$order->getGrandTotal()}");
                }
                if (empty($order->getBaseTotalPaid()) || $order->getBaseTotalPaid() == "0.0000") {
                    $order->setBaseTotalPaid($order->getBaseGrandTotal());
                    $logger->info("Base Total Paid #{$order->getBaseGrandTotal()}");
                }
                if (empty($order->getTotalInvoiced()) || $order->getTotalInvoiced() == "0.0000") {
                    $order->setTotalInvoiced($order->getGrandTotal());
                    $logger->info("Total Invoiced #{$order->getGrandTotal()}");
                }
                if (empty($order->getBaseTotalInvoiced()) || $order->getBaseTotalInvoiced() == "0.0000") {
                    $order->setBaseTotalInvoiced($order->getBaseGrandTotal());
                    $logger->info("Base Total Invoiced #{$order->getBaseGrandTotal()}");
                }
                if ($flag == 1) {
                    $order->setTotalDue("0.0000");
                    $logger->info("Total Due changed");
                    $order->setBaseTotalDue("0.0000");
                    $logger->info(" Base Total Due changed");
                    $order->save();
                }
            }
        }
    }

    /**
     * Get config hours.
     *
     * @return mixed
     */
    private function getConfigHours()
    {
        return $this->scopeConfig->getValue("onitsukatiger_sales/sales_update_invoice_qty/order_hours_frequency_to_update_qty_invoiced", ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null);
    }

    /**
     * @return string[]|null
     */
    private function getOrderStatus()
    {
        $orderStatus = $this->scopeConfig->getValue("onitsukatiger_sales/sales_update_invoice_qty/order_status", ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null);
        return explode(",", $orderStatus);
    }

    /**
     * @return array
     */
    private function isEnabledForStore()
    {
        $websiteIds = [];
        $websites = $this->storeManager->getWebsites();
        foreach ($websites as $website) {
            $isEnabled = $this->scopeConfig->getValue(
                "onitsukatiger_sales/sales_update_invoice_qty/enabled",
                ScopeInterface::SCOPE_WEBSITE,
                $website
            );
            if ($isEnabled) {
                foreach ($website->getStoreIds() as $storeId) {
                    $websiteIds[] = $storeId;
                }
            }
        }
        return $websiteIds;
    }
}
