<?php
namespace OnitsukaTiger\Gthk\Model;

use Exception;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\Gthk\Api\CallbackInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\StoreRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\KerryConNo\Model\TrackingNumber;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\NetSuite\Model\SuiteTalk\UpdateShipmentStatusToNetsuite;
use OnitsukaTiger\Cegid\Model\UpdateShipmentStatusToCegid;
use OnitsukaTiger\Rma\Helper\NotDelivered;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Sales\Api\Data\CreditmemoItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\CreditmemoItemCreationInterface;
use Magento\Sales\Api\RefundOrderInterface;

class Callback implements CallbackInterface
{
    const EVENT_SEND_EMAIL_DELIVERED = 'netsuite_update_order_status_delivered';

    public Http $httpRequest;
    public StoreRepository $storeRepository;
    public LoggerInterface $logger;
    public ScopeConfigInterface $config;
    public OrderResource $orderResource;
    public TrackingNumber $trackingNumber;
    public ShipmentStatus $shipmentStatusModel;
    public OrderStatus $orderStatusModel;
    public ManagerInterface $eventManager;
    public UpdateShipmentStatusToNetsuite $updateShipmentStatusToNetsuite;
    public SourceRepositoryInterface $sourceRepository;
    public UpdateShipmentStatusToCegid $updateShipmentStatusToCegid;
    public ShipmentRepositoryInterface $shipmentRepository;
    public NotDelivered $notDelivered;

    private CreditmemoFactory $creditmemoFactory;
    private CreditmemoService $creditmemoService;
    private Transaction $transaction;
    private ScopeConfigInterface $scopeConfig;
    private CreditmemoItemCreationInterfaceFactory $creditmemoItemFactory;
    private RefundOrderInterface $refundOrder;

    public function __construct(
        Http $httpRequest,
        StoreRepository $storeRepository,
        LoggerInterface $logger,
        ScopeConfigInterface $config,
        OrderResource $orderResource,
        TrackingNumber $trackingNumber,
        ShipmentStatus $shipmentStatusModel,
        OrderStatus $orderStatusModel,
        ManagerInterface $eventManager,
        NotDelivered $notDelivered,
        UpdateShipmentStatusToNetsuite $updateShipmentStatusToNetsuite,
        SourceRepositoryInterface $sourceRepository,
        UpdateShipmentStatusToCegid $updateShipmentStatusToCegid,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        Transaction $transaction,
        ScopeConfigInterface $scopeConfig,
        CreditmemoItemCreationInterfaceFactory $creditmemoItemFactory,
        RefundOrderInterface $refundOrder,
        ShipmentRepositoryInterface $shipmentRepository
    ) {
        $this->httpRequest = $httpRequest;
        $this->storeRepository = $storeRepository;
        $this->logger = $logger;
        $this->config = $config;
        $this->orderResource = $orderResource;
        $this->trackingNumber = $trackingNumber;
        $this->shipmentStatusModel = $shipmentStatusModel;
        $this->orderStatusModel = $orderStatusModel;
        $this->eventManager = $eventManager;
        $this->updateShipmentStatusToNetsuite = $updateShipmentStatusToNetsuite;
        $this->sourceRepository = $sourceRepository;
        $this->updateShipmentStatusToCegid = $updateShipmentStatusToCegid;
        $this->shipmentRepository = $shipmentRepository;
        $this->notDelivered = $notDelivered;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->transaction = $transaction;
        $this->scopeConfig = $scopeConfig;
        $this->creditmemoItemFactory = $creditmemoItemFactory;
        $this->refundOrder = $refundOrder;
    }

    /**
     * @return string|string[]|void
     */
    public function handleWebhook()
    {
        $date = date('Y-m-d'); // or 'Y-m-d_H' for hourly logs
        $logFile = BP . "/var/log/ghtk_api_wabhook_{$date}.log";

        $writer = new \Zend_Log_Writer_Stream($logFile);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        try {
            $content = $this->httpRequest->getContent();
            $this->isTrackingIdExits($content);
            $json = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger->error('GHTK callback: invalid JSON payload');
                return 'Invalid JSON';
            }
            $statusId = $json['status_id'] ?? null;
            $partnerId = $json['partner_id'] ?? null;

            if (!$statusId || !$partnerId) {
                throw new WebapiException(__('Missing required fields in payload.'));
            }
            $labelIdFull = $json['label_id'] ?? '1234567895';
            $labelIdParts = explode('.', $labelIdFull);
            $labelId = end($labelIdParts);

            $logger->info('Extracted Label ID: ' . $labelId);
            $shipment = $this->trackingNumber->getShipmentFromTrackingNumber($labelId);
            if (!$shipment) {
                $this->throwWebApiException("shipment doesn't exits", 400);
            }
            $this->validateShipment($shipment, [ShipmentStatus::STATUS_SHIPPED]);

            if ($json['status_id'] == 5) {
                $this->shipmentStatusModel->updateStatus($shipment, ShipmentStatus::STATUS_DELIVERED);
                $this->orderStatusModel->setOrderStatus($shipment->getOrder());
                $this->eventManager->dispatch(self::EVENT_SEND_EMAIL_DELIVERED, ['shipment' => $shipment]);
                $logger->info("Order {$partnerId} marked as delivered.");
                if (in_array($shipment->getStoreId(), [8, 10])) {
                    $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
                    try {
                        $source = $this->sourceRepository->get($sourceCode);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $logger->info($e->getMessage());
                    }
                    try {
                        if ($source && $source->getIsShippingFromStore()) {
                            $this->updateShipmentStatusToCegid->execute($shipment, ShipmentStatus::STATUS_DELIVERED);
                        } else {
                            $this->updateShipmentStatusToNetsuite->execute($shipment, ShipmentStatus::STATUS_DELIVERED);
                        }
                    } catch (Exception $e) {
                        $logger->info($e->getMessage());
                    }
                }
                return ['success' => "delivered", 'message' => 'Order marked as delivered successfully.'];
            }
            if ($json['status_id'] == 9) {
                try {
                    $this->notDelivered->makeNotDeliveredRequest($shipment, $labelId);
                    $this->shipmentStatusModel->updateStatus($shipment, ShipmentStatus::STATUS_DELIVERY_FAILED);
                    $this->orderStatusModel->setOrderStatus($shipment->getOrder());

                    /** @var \Magento\Sales\Model\Order $order */
                    $order = $shipment->getOrder();

                    $invoice = $order->getInvoiceCollection()->getFirstItem();
                    $shippedQtyByOrderItemId = [];
                    foreach ($shipment->getAllItems() as $shipItem) {
                        $orderItemId = (int)$shipItem->getOrderItemId();
                        $qtyShipped = (float)$shipItem->getQty();
                        if ($orderItemId <= 0 || $qtyShipped <= 0.0) {
                            continue;
                        }
                        if (!isset($shippedQtyByOrderItemId[$orderItemId])) {
                            $shippedQtyByOrderItemId[$orderItemId] = 0.0;
                        }
                        $shippedQtyByOrderItemId[$orderItemId] += $qtyShipped;
                    }

                    // If we don't have an invoice or no shipped items to refund, fallback to offline creditmemo
                    if (!($invoice && $invoice->getId()) || empty($shippedQtyByOrderItemId)) {
                        $creditmemo = $this->creditmemoFactory->createByOrder($order);
                        $creditmemo->setOfflineRequested(true);
                        $creditmemo->register();
                        $creditmemo->addComment('Credit memo created automatically (offline).', false, false);
                        $this->transaction->addObject($creditmemo)->addObject($order)->save();
                        $logger->info("Offline creditmemo created for order {$order->getIncrementId()} (no invoice or no shipped items)."); 
                    } else {
                        /** @var \Magento\Sales\Api\Data\CreditmemoItemCreationInterface[] $items */
                        $items = [];

                        // Iterate invoice items and compute per-item refundable qty (respecting previous refunds/cancellations)
                        foreach ($invoice->getItems() as $invoiceItem) {
                            $orderItem = $invoiceItem->getOrderItem();
                            if (!$orderItem) {
                                continue;
                            }
                            if ($orderItem->getParentItemId()) {
                                continue;
                            }

                            $orderItemId = (int)$orderItem->getId();
                            if ($orderItemId <= 0) {
                                continue;
                            }

                            // Quantities to determine what's left refundable
                            $qtyInvoiced  = (float)$orderItem->getQtyInvoiced();
                            $qtyRefunded  = (float)$orderItem->getQtyRefunded();
                            $qtyCanceled  = (float)$orderItem->getQtyCanceled();

                            // Compute remaining refundable qty
                            $refundableQtyRemaining = $qtyInvoiced - $qtyRefunded - $qtyCanceled;
                            if ($refundableQtyRemaining <= 0.0) {
                                $logger->debug("No refundable qty left for order {$order->getIncrementId()} order_item {$orderItemId}.");
                                continue;
                            }

                            // Qty shipped in THIS shipment for this order item (may be zero)
                            $shippedHere = isset($shippedQtyByOrderItemId[$orderItemId]) ? (float)$shippedQtyByOrderItemId[$orderItemId] : 0.0;
                            if ($shippedHere <= 0.0) {
                                continue;
                            }

                            // Final qty to refund is the minimum of refundable remaining and qty shipped here
                            $qtyToRefund = min($refundableQtyRemaining, $shippedHere);

                            if ($qtyToRefund <= 0.0) {
                                continue;
                            }

                            $creditmemoItemCreation = $this->creditmemoItemFactory->create();
                            $creditmemoItemCreation->setQty((float)$qtyToRefund);
                            $creditmemoItemCreation->setOrderItemId($orderItemId);
                            $items[] = $creditmemoItemCreation;

                            $logger->info(sprintf(
                                "Prepared refund for order %s: order_item_id %d, qtyInvoiced=%.4f, qtyRefunded=%.4f, qtyCanceled=%.4f, shippedHere=%.4f, refund=%.4f",
                                $order->getIncrementId(),
                                $orderItemId,
                                $qtyInvoiced,
                                $qtyRefunded,
                                $qtyCanceled,
                                $shippedHere,
                                $qtyToRefund
                            ));
                        }

                        if (empty($items)) {
                            $logger->info("No refundable invoice items found in shipment for order {$order->getIncrementId()}; skipping creditmemo.");
                            return;
                        }

                        /*try {
                            $this->refundOrder->execute((int)$order->getEntityId(), $items, true, false);
                            $logger->info("Online creditmemo created for order {$order->getIncrementId()} for shipment items.");
                        } catch (\Exception $e) {
                            // If online refund fails, fallback to offline creation (preserve order state)
                            $logger->error("Online refund failed for order {$order->getIncrementId()}: " . $e->getMessage());
                            try {
                                $creditmemo = $this->creditmemoFactory->createByOrder($order);
                                $creditmemo->setOfflineRequested(true);
                                $creditmemo->register();
                                $creditmemo->addComment('Credit memo created automatically (offline) after online refund failure.', false, false);
                                $this->transaction->addObject($creditmemo)->addObject($order)->save();
                                $logger->info("Offline creditmemo created (after online failure) for order {$order->getIncrementId()}.");
                            } catch (\Exception $inner) {
                                // If that also fails, log and rethrow the original exception
                                $logger->info("Fallback offline creditmemo also failed for order {$order->getIncrementId()}: " . $inner->getMessage());
                                throw $e;
                            }
                        }*/
                    }

                    if (in_array($shipment->getStoreId(), [8, 10], true)) {
                        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
                        try {
                            $source = $this->sourceRepository->get($sourceCode);
                        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                            $logger->info($e->getMessage());
                            $source = null;
                        }

                        try {
                            if ($source && $source->getIsShippingFromStore()) {
                                $this->updateShipmentStatusToCegid->execute($shipment, ShipmentStatus::STATUS_DELIVERY_FAILED);
                            } else {
                                // $this->updateShipmentStatusToNetsuite->execute($shipment, ShipmentStatus::STATUS_DELIVERY_FAILED);
                            }
                        } catch (\Exception $e) {
                            $logger->info($e->getMessage());
                        }
                    }

                    return ['success' => "delivery_failed", 'message' => 'Order marked as delivery_failed successfully.'];
                } catch (\Exception $e) {
                    $logger->info($e->getMessage());
                    $this->throwWebApiException($e->getMessage(), 500);
                }
            }

            if ($json['status_id'] != 9 && $json['status_id'] != 5) {
                return ['success' => "Please check your status_id for further details about the order process"];
            }
        } catch (\Throwable $e) {
            $logger->info('Callback error: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * @param $content
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function isTrackingIdExits($content)
    {
        $date = date('Y-m-d'); // or 'Y-m-d_H' for hourly logs
        $logFile = BP . "/var/log/ghtk_api_wabhook_{$date}.log";

        $writer = new \Zend_Log_Writer_Stream($logFile);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $json = json_decode($content, true);
        if (!is_array($json) || !array_key_exists('label_id', $json)) {
            $msg = 'could not take label_id : ' . $content;
            $logger->info($msg);
            $this->throwWebApiException($msg, 400);
        }
    }

    /**
     * Throw Web API exception and add it to log
     * @param $msg
     * @param $status
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function throwWebApiException($msg, $status)
    {
        $exception = new \Magento\Framework\Webapi\Exception(__($msg), $status);
        $this->logger->critical($exception);
        throw $exception;
    }

    /**
     * @param $shipment
     * @param array $status
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function validateShipment($shipment, array $status)
    {
        $ext = $shipment->getExtensionAttributes();
        if (!in_array($ext->getStatus(), $status)) {
            $this->throwWebApiException(sprintf('shipment id [%s] is not status[%s]', $shipment->getIncrementId(), implode(', ', $status)), 400);
        }
    }
}
