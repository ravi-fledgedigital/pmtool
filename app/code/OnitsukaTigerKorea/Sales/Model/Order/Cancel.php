<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Sales\Model\Order;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Service\CreditmemoService;
use OnitsukaTiger\CancelShipment\Model\Shipment\Cancel as ShipmentCancel;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;

class Cancel {

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var ShipmentStatus
     */
    protected $shipmentStatus;

    /**
     * @var CreditmemoFactory
     */
    protected $creditMemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditMemoService;

    /**
     * @var CreditmemoManagementInterface
     */
    protected $creditMemoManagement;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @var CreditmemoSender
     */
    protected $creditmemoSender;

    /**
     * @var Logger
     */
    protected $logger;

    protected $shipmentCancel;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentStatus $shipmentStatus
     * @param CreditmemoFactory $creditMemoFactory
     * @param CreditmemoService $creditMemoService
     * @param ManagerInterface $eventManager
     * @param StockConfigurationInterface $stockConfiguration
     * @param CreditmemoSender $creditmemoSender
     * @param CreditmemoManagementInterface $creditMemoManagement
     * @param ShipmentCancel $shipmentCancel
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ShipmentRepositoryInterface  $shipmentRepository,
        ShipmentStatus $shipmentStatus,
        CreditmemoFactory $creditMemoFactory,
        CreditmemoService $creditMemoService,
        ManagerInterface $eventManager,
        StockConfigurationInterface $stockConfiguration,
        CreditmemoSender $creditmemoSender,
        CreditmemoManagementInterface $creditMemoManagement,
        ShipmentCancel $shipmentCancel,
        Logger $logger
    )
    {
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentStatus = $shipmentStatus;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->creditMemoService = $creditMemoService;
        $this->eventManager = $eventManager;
        $this->stockConfiguration = $stockConfiguration;
        $this->creditmemoSender = $creditmemoSender;
        $this->creditMemoManagement = $creditMemoManagement;
        $this->shipmentCancel = $shipmentCancel;
        $this->logger = $logger;
    }
    /**
     * @param Order $order
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteShipmentWhenCancelOrder(Order $order)
    {
        $shipments = $this->shipmentStatus->getShipmentDataByOrderId($order->getEntityId());
        foreach($shipments as $shipment) {
            $this->shipmentCancel->execute($shipment);
        }

        $order->addCommentToStatusHistory('The Order has been canceled by Customer');
        $this->orderRepository->save($order);
    }

    /**
     * @param Order $order
     * @param $reason
     * @return void
     */
    public function addOrderCancelReason(Order $order, $reason): void
    {
        $order->addCommentToStatusHistory('Cancel Reason: ' . $reason);
        $this->orderRepository->save($order);
    }

    /**
     * @param Order $order
     * @return false|void
     */
    public function createCreditMemoWhenCustomerCancelOrder(Order $order) {
        try {
            $items = [];
            $do_offline = 1;
            if($order->getPayment()->getMethodInstance()->isGateway()){
                $do_offline = 0;
            }
            $invoice =  $order->getInvoiceCollection()->getFirstItem();

            foreach($order->getAllVisibleItems() as $item) {
                $items[$item['item_id']] = [
                    'back_to_stock' => 1,
                    'qty' => $item['qty_invoiced']
                ];
            }
            $data = [
                'items' => $items,
                'do_offline'=> $do_offline,
                'comment_text'=>"",
                'shipping_amount'=>'0',
                'adjustment_positive'=>"",
                "adjustment_negative"=>""
            ];
            if (!$this->_canCreditmemo($order)) {
                return false;
            }
            $savedData = $data['items'];
            $qtys = [];
            $backToStock = [];
            foreach ($savedData as $orderItemId => $itemData) {
                if (isset($itemData['qty'])) {
                    $qtys[$orderItemId] = $itemData['qty'];
                }
                if (isset($itemData['back_to_stock'])) {
                    $backToStock[$orderItemId] = true;
                }
            }
            $data['qtys'] = $qtys;

            if ($invoice) {
                $creditmemo = $this->creditMemoFactory->createByInvoice($invoice, $data);
            } else {
                $creditmemo = $this->creditMemoFactory->createByOrder($order, $data);
            }

            /**
             * Process back to stock flags
             */
            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                $orderItem = $creditmemoItem->getOrderItem();
                $parentId = $orderItem->getParentItemId();
                if ($parentId && isset($backToStock[$parentId]) && $backToStock[$parentId]) {
                    $creditmemoItem->setBackToStock(true);
                } elseif (isset($backToStock[$orderItem->getId()])) {
                    $creditmemoItem->setBackToStock(true);
                } elseif (empty($savedData)) {
                    $creditmemoItem->setBackToStock(
                        $this->stockConfiguration->isAutoReturnEnabled()
                    );
                } else {
                    $creditmemoItem->setBackToStock(false);
                }
            }

            $this->eventManager->dispatch(
                'adminhtml_sales_order_creditmemo_register_before',
                ['creditmemo' => $creditmemo, 'input' => $data]
            );


            $creditmemo->getOrder()->setCustomerNoteNotify(!empty($order->getCustomerEmail()));
            $this->creditMemoManagement->refund($creditmemo, (bool)$data['do_offline']);

            if (!empty($order->getCustomerEmail())) {
                $this->creditmemoSender->send($creditmemo);
            }


        }catch (\Exception $e) {
            $this->logger->error(sprintf('Create Credit Memo when Customer Cancel has error %s', $e->getMessage()));
        }


    }

    /**
     * Check if creditmeno can be created for order
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function _canCreditmemo($order)
    {
        /**
         * Check order existing
         */
        if (!$order->getId()) {
            return false;
        }

        /**
         * Check creditmemo create availability
         */
        if (!$order->canCreditmemo()) {
            return false;
        }
        return true;
    }

}
