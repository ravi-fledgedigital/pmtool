<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Shipping\Model\PartialCancel\Process;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use OnitsukaTiger\Logger\Api\Logger;

class CreditMemoItemCanceled {

    /**
     * @var CreditmemoFactory
     */
    protected $creditMemoFactory;

    /**
     * @var StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var CreditmemoManagementInterface
     */
    protected $creditMemoManagement;

    /**
     * @var CreditmemoSender
     */
    protected $creditmemoSender;

    /**
     * @var Logger
     */
    protected $logger;


    public function __construct(
        CreditmemoSender $creditmemoSender,
        CreditmemoManagementInterface $creditMemoManagement,
        ManagerInterface $eventManager,
        StockConfigurationInterface $stockConfiguration,
        CreditmemoFactory $creditMemoFactory,
        Logger $logger
    )
    {
        $this->creditmemoSender = $creditmemoSender;
        $this->creditMemoManagement = $creditMemoManagement;
        $this->eventManager = $eventManager;
        $this->stockConfiguration = $stockConfiguration;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->logger = $logger;
    }

    /**
     * Check if creditmeno can be created for order
     * @param $order
     * @return false
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


    public function execute(OrderInterface $order, array $data){

        try {
            $items = [];
            $do_offline = 1;
            if($order->getPayment()->getMethodInstance()->isGateway()){
                $do_offline = 0;
            }
            $invoice =  $order->getInvoiceCollection()->getFirstItem();
            $itemsCancelQty = $data['cancel_items'] ?? [];
            foreach($order->getAllVisibleItems() as $item) {
                foreach($itemsCancelQty as $itemId => $quantity) {
                    if($item->getItemId() == $itemId) {
                        $items[$item['item_id']] = [
                            'back_to_stock' => 1,
                            'qty' => $quantity
                        ];
                    }
                }
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
            $this->logger->error(sprintf('Create Credit Memo when cancel item has error %s', $e->getMessage()));
        }
    }
}
