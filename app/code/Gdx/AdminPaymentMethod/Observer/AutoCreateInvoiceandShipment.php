<?php

namespace Gdx\AdminPaymentMethod\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AutoCreateInvoice
 *
 * @package Gdx\AdminPaymentMethod\Observer
 */
class AutoCreateInvoiceandShipment implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transaction;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;

    /**
     * @var \Magento\Shipping\Model\ShipmentNotifier
     */
    protected $shipmentNotifier;

    /**
     * AutoCreateInvoice constructor.
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\DB\TransactionFactory $transaction
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     * @param \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier
     */
    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\DB\TransactionFactory $transaction,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier
    ) {
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->messageManager = $messageManager;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment()->getMethodInstance();

        // Check code payment method
        if ($payment->getCode() == 'adminpaymentmethod') {

            $this->createShipment($payment, $order);

            $this->createInvoice($payment, $order);

            $this->displayNotified($order, $payment);
        }
    }

    /**
     * @param $payment
     * @param $order
     * @return |null
     */
    private function createInvoice($payment, $order)
    {
        if ($payment->getConfigData('createinvoice')) {
            try {
                if (!$order->canInvoice() || !$order->getState() == 'new') {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('You cant create the Invoice of this order.')
                    );
                }

                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);
                $transaction = $this->transaction->create()->addObject($invoice)->addObject($invoice->getOrder());
                $transaction->save();
                //Show message create invoice
                $this->messageManager->addSuccessMessage(__("Automatically generated Invoice."));
            } catch (\Exception $e) {
                $order->addStatusHistoryComment('Exception message: ' . $e->getMessage(), false);
                $order->save();
            }
        }
    }

    /**
     * @param $payment
     * @param $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createShipment($payment, $order)
    {
        if ($payment->getConfigData('createshipment')) {
            // to check order can ship or not
            if (!$order->canShip()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('You cant create the Shipment of this order.')
                );
            }
            $orderShipment = $this->convertOrder->toShipment($order);

            foreach ($order->getAllItems() as $orderItem) {
                // Check virtual item and item Quantity
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }
                $qty = $orderItem->getQtyToShip();
                $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);

                $orderShipment->addItem($shipmentItem);
            }

            $orderShipment->register();
            $orderShipment->getOrder()->setIsInProcess(true);
            try {
                // Send Shipment Email
                $this->shipmentNotifier->notify($orderShipment);
                $orderShipment->save();

                // Save created Order Shipment
                $orderShipment->save();
                $orderShipment->getOrder()->save();

                //Show message create shipment
                $this->messageManager->addSuccessMessage(__("Automatically generated Shipment."));
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
            }
        }
    }

    /**
     * @param $order
     * @param $payment
     * @return |null
     */
    private function displayNotified($order, $payment)
    {
        try {
            if ($payment->getConfigData('createinvoice') && $payment->getConfigData('createshipment')) {
                return $order->addStatusHistoryComment(__('Automatically Invoice and Shipment By Gdx Invoice Shipment'))->save();
            } elseif ($payment->getConfigData('createinvoice')) {
                return $order->addStatusHistoryComment(__('Automatically Invoice By Gdx Invoice'))->save();
            } elseif ($payment->getConfigData('createshipment')) {
                return $order->addStatusHistoryComment(__('Automatically Shipment By Gdx Shipment'))->save();
            }
            return null;
        } catch (\Exception $e) {
            $order->addStatusHistoryComment('Exception message: ' . $e->getMessage(), false);
            $order->save();
            return null;
        }
    }
}
