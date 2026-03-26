<?php
/** phpcs:ignoreFile */
namespace OnitsukaTiger\PreOrders\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use OnitsukaTiger\PreOrders\Helper\PreOrder;

class PreOrderSalesOrderAfterSave implements ObserverInterface
{
    const PRODUCT_TYPE = 'simple';

    /**
     * @var PreOrder
     */
    protected $preOrderHelper;

    /**
     * @var \MMagento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    public function __construct(
        PreOrder $preOrderHelper,
        OrderItemRepositoryInterface $orderItemRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->preOrderHelper = $preOrderHelper;
        $this->orderItemRepository = $orderItemRepository;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/pre_order_invoice.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $invoice = $observer->getEvent()->getInvoice();
        $isPreOrder = false;
        $items = $invoice->getAllItems();
        $order = $invoice->getOrder();

        foreach ($items as $item) {
            $logger->info('-------invoice item id  - ' . $item->getOrderItemId());
            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
            $logger->info('product id  - ' . $orderItem->getProductId() . ' and type - ' . $orderItem->getProductType());
            if ($orderItem->getProductType() == self::PRODUCT_TYPE) {
                $logger->info('simple product id  - ' . $item->getProductId());
                $logger->info('simple product store id  - ' . $item->getStoreId());
                if ($this->preOrderHelper->isProductPreOrder($item->getProductId(), $order->getStoreId())) {
                    $logger->info('isPreOrder -  true');
                    $isPreOrder = true;
                    break;
                }
            }
        }

        if ($isPreOrder) {
            $logger->info('is preorder if condition.');
            $order = $invoice->getOrder();
            if ($order->getStoreId() == 6 || $order->getStoreId() == 7) {
                $logger->info('if condition for status update.');
                $order->setState("processing")->setStatus("pre_order_processing");
                $connection = $this->resourceConnection->getConnection();
                $connection->update(
                    $this->resourceConnection->getTableName('sales_order'),
                    ['state' => 'processing', 'status' => 'pre_order_processing'],
                    ['entity_id = ?' => $order->getEntityId()]
                );
                $connection->update(
                    $this->resourceConnection->getTableName('sales_order_grid'),
                    ['status' => 'pre_order_processing'],
                    ['entity_id = ?' => $invoice->getEntityId()]
                );
            } else {
                $logger->info('else condition for status update.');
                $order->setState("processing")->setStatus("pre_order_processing");
            }
            $order->save();
        }
        return $this;
    }
}
