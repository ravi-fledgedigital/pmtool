<?php
declare(strict_types=1);
namespace OnitsukaTiger\Cegid\Model;

use Amasty\Rma\Model\Request\ResourceModel\RequestItemCollectionFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\CancelShipment\Model\Shipment\Cancel as ShipmentCancel;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;

class CreditMemo
{
    const CREDITMEMO_SUCCESS = 3;
    private OrderRepositoryInterface $orderRepository;
    private ShipmentRepositoryInterface $shipmentRepository;
    private ShipmentStatus $shipmentStatus;
    private CreditmemoFactory $creditMemoFactory;
    private CreditmemoService $creditMemoService;
    private ManagerInterface $eventManager;
    private StockConfigurationInterface $stockConfiguration;
    private CreditmemoSender $creditmemoSender;
    private CreditmemoManagementInterface $creditMemoManagement;
    private ShipmentCancel $shipmentCancel;
    private Logger $logger;
    private StoreManagerInterface $storeManager;
    private RequestItemCollectionFactory $requestItemCollection;
    private \OnitsukaTiger\Cegid\Model\ReturnActionRepository $returnActionRepository;

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
     * @param StoreManagerInterface $storeManager
     * @param RequestItemCollectionFactory $requestItemCollection
     * @param ReturnActionRepository $returnActionRepository
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
        Logger $logger,
        StoreManagerInterface   $storeManager,
        RequestItemCollectionFactory   $requestItemCollection,
        ReturnActionRepository      $returnActionRepository
    ) {
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
        $this->storeManager = $storeManager;
        $this->requestItemCollection = $requestItemCollection;
        $this->returnActionRepository = $returnActionRepository;
    }

    /**
     * @param Order $order
     * @param $requestId
     * @return false|void
     */
    public function createCreditMemo(Order $order, $requestId, $returnActionId)
    {
        $stores = $this->storeManager->getStores(true, true);
        try {
            $items = [];
            $do_offline = 1;
            if ($order->getPayment()->getMethodInstance()->isGateway() &&
                ($order->getStoreId() == $stores['web_sg_en']->getStoreId()
                || $order->getStoreId() == $stores['web_th_en']->getStoreId()
                || $order->getStoreId() == $stores['web_th_th']->getStoreId()
                    || $order->getStoreId() == $stores['web_vn_vi']->getStoreId()
                    || $order->getStoreId() == $stores['web_vn_en']->getStoreId())
            ) {
                $do_offline = 0;
            }
            $invoice =  $order->getInvoiceCollection()->getFirstItem();

            foreach ($order->getItems() as $item) {
                if ($item->getProductType() == "configurable") {
                    continue;
                }
                $qtyRequestItem = $this->getQtyRequestItem($requestId, $item->getId());
                $items[$item->getParentItemId()] = [
                    'back_to_stock' => 1,
                    'qty' => $qtyRequestItem
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
            $returnActionRepository = $this->returnActionRepository->get($returnActionId);
            $returnActionRepository->setStatus(self::CREDITMEMO_SUCCESS);
            $returnActionRepository->save();
            if (!empty($order->getCustomerEmail())) {
                $this->creditmemoSender->send($creditmemo);
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Create Credit Memo when call api Cegid has error %s', $e->getMessage()));
        }
    }

    /**
     * Check if creditmeno can be created for order
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function _canCreditmemo($order): bool
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

    /**
     * @param $requestId
     * @return int
     */
    public function getQtyRequestItem($requestId, $orderItemId)
    {
        $qty = 0;
        $requestItemCollection = $this->requestItemCollection->create()
            ->addFieldToFilter("request_id", $requestId)
            ->addFieldToFilter("order_item_id", $orderItemId);
        foreach ($requestItemCollection as $item) {
            $qty += $item->getQty();
        }
        return $qty;
    }
}
