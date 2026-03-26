<?php

namespace OnitsukaTiger\PortOne\Model;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\PortOne\Api\WebhookDataInterface;
use OnitsukaTiger\PortOne\Helper\Data as PortOneHelper;
use OnitsukaTiger\PortOne\Logger\Logger;
use OnitsukaTiger\PortOne\Model\ResourceModel\PortOne\CollectionFactory as PortOneCollectionFactory;
use OnitsukaTiger\PortOne\Model\PortOneFactory;

class WebhookData implements WebhookDataInterface
{
    protected $orderRepository;
    protected $portOneHelper;
    protected $logger;
    protected $portOneCollectionFactory;
    protected $portoneFactory;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PortOneHelper $portOneHelper,
        Logger $logger,
        PortOneCollectionFactory $portOneCollectionFactory,
        PortOneFactory $portoneFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->portOneHelper = $portOneHelper;
        $this->logger = $logger;
        $this->portOneCollectionFactory = $portOneCollectionFactory;
        $this->portoneFactory = $portoneFactory;
    }

    /**
     * Manage webhook payload.
     *
     * @param string $tx_id
     * @param string $payment_id
     * @param string $status
     * @return string
     */
    public function handleWebhookData(string $tx_id, string $payment_id, string $status): string
    {
        try {
            $paymentId      = $payment_id ?? null;
            $transactionId  = $tx_id ?? null;

            if (!$transactionId) {
                $this->logger->info('Transaction Id not found: ' . $transactionId);
                return 'Transaction ID is required';
            }

            $portOneModel = $this->portOneCollectionFactory->create()
                ->addFieldToFilter('payment_id', $paymentId)
                ->getFirstItem();

            if (!$portOneModel || !$portOneModel->getId()) {
                $this->logger->info('Payment Id not found: ' . $paymentId);
                return 'PortOne payment_id not found.';
            }

            $orderId = $portOneModel->getOrderEntityId();
            $order = $this->orderRepository->get($orderId);

            if ($status == 'Ready') {
                $order->setState(\Magento\Sales\Model\Order::STATE_NEW)
                      ->setStatus('portone_authorized')
                      ->addStatusHistoryComment(__('Order #%1 authorized.', $order->getIncrementId()))
                      ->setIsCustomerNotified(false);
                $order->save();
                $this->logger->info("Order {$order->getIncrementId()} status set to portone_authorized.");
                return sprintf(
                    'Order #%s status has been successfully updated to "portone_authorized".',
                    $order->getIncrementId()
                );
            }

            if ($status == 'Paid') {
                $response = $this->portOneHelper->generateInvoice($order);
                $this->logger->info($response['message']);
                return $response['message'];
            }

            if ($status == 'Failed') {
                $order->cancel();
                $order->addStatusHistoryComment(__('Order #%1 canceled.', $order->getIncrementId()));
                $order->save();
                $this->logger->info("Order {$order->getIncrementId()} status set to canceled.");
                return sprintf(
                    'Order #%s status has been successfully updated to "canceled".',
                    $order->getIncrementId()
                );
            }

            if ($status !== 'Cancelled') {
                return 'Unsupported webhook status';
            }

            if (!$order->getEntityId()) {
                return "Order ID $orderId not found.";
            }

            $oldStatus = $order->getStatus();
            $response = $this->portOneHelper->generateCreditMemo($order);

            $portoneModel = $this->portoneFactory->create();
            $transactionType = ($response['success']) ? 'Refund' : 'Failed';
            $status = ($response['success']) ? 2 : 0;

            $portoneModel->setData([
                'order_entity_id'  => $order->getEntityId(),
                'payment_id'       => $paymentId,
                'transaction_type' => $transactionType,
                'txid'             => $transactionId,
                'full_content'     => null,
                'status'           => $status,
                'message'          => $response['message'] ?? 'Something went wrong.'
            ]);
            $portoneModel->save();
            if($response['success']) {
                $this->orderRepository->save($order);
                $this->logger->info("Credit memo generated: Order #" . $order->getIncrementId() .", old status: '" . $oldStatus ."', new status: '" . $response['status'] . "'");
                return "Credit memo successfully generated for Order #{$order->getIncrementId()}.";
            } else {
                $this->logger->info($response['message']);
                return $response['message'];
            }
        } catch (NoSuchEntityException $e) {
            return "Order not found: " . $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->error("Credit memo generation failed: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }
}