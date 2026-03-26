<?php
namespace OnitsukaTiger\PortOne\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use OnitsukaTiger\PortOne\Model\ResourceModel\PortOne\Collection as PortOneCollection;
use OnitsukaTiger\PortOne\Logger\Logger;
use Magento\Sales\Model\OrderFactory;

class SavePaymentInfo extends Action
{
    protected $resultJsonFactory;
    protected $portOneCollection;
    protected $logger;
    protected $orderFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PortOneCollection $portOneCollection,
        Logger $logger,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->portOneCollection = $portOneCollection;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $data = json_decode($this->getRequest()->getContent(), true);

            if (!isset($data['response'])) {
                return $result->setData(['success' => false, 'message' => 'Missing response data']);
            }

            $response = $data['response'];

            if (!isset($response['portone_payment_order_id'])) {
                return $result->setData(['success' => false, 'message' => 'Missing order ID']);
            }

            $orderId = $response['portone_payment_order_id'];

            $order = $this->orderFactory->create()->load($orderId);
            if (!$order->getId()) {
                return $result->setData(['success' => false, 'message' => 'Order not found']);
            }

            $status = (isset($response['portone_payment_status']) && $response['portone_payment_status'] === 'success') ? 1 : 0;
            $paymentId = $response['portone_payment_id'] ?? null;
            if($orderId && $paymentId) {
                $collection = $this->portOneCollection
                    ->addFieldToFilter('order_entity_id', $orderId)
                    ->addFieldToFilter('payment_id', $paymentId);

                $portoneModel = $collection->getFirstItem();

                if ($portoneModel && $portoneModel->getId()) {
                    $portoneModel->setTransactionType($response['portone_transaction_type'] ?? null)
                                 ->setTxid($response['portone_tx_id'] ?? null)
                                 ->setFullContent($response['portone_full_response'] ?? '')
                                 ->setStatus($status)
                                 ->setMessage($response['portone_failure_reason'] ?? null);
                    $portoneModel->save();
                }
            }

            $this->logger->info('PortOne payment saved', [
                'order_id'   => $order->getIncrementId(),
                'status'     => $status ? 'success' : 'fail',
                'payment_id' => $response['portone_payment_id'] ?? null,
                'reason'     => $response['portone_failure_reason'] ?? null
            ]);

            $historyComment = $status
                ? __(
                    'PortOne payment successful.<br /><b>Payment ID:</b> %1<br /><b>TxID:</b> %2',
                    $response['portone_payment_id'] ?? '-',
                    $response['portone_tx_id'] ?? '-'
                )
                : __(
                    'PortOne payment failed.<br /><b>Reason:</b> %1',
                    $response['portone_failure_reason'] ?? 'Unknown'
                );

            $order->addStatusHistoryComment($historyComment, false);
            $order->save();

            if ($status === 0 && $order->canCancel()) {
                $order->cancel()->save();
                $this->logger->warning('Order cancelled due to failed payment', [
                    'order_id' => $order->getIncrementId()
                ]);
            }
            $this->logger->info('***********************************************');
            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
