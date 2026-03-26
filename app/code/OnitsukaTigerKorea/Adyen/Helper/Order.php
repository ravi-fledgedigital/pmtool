<?php

namespace OnitsukaTigerKorea\Adyen\Helper;

use Adyen\Payment\Api\Data\OrderPaymentInterface;
use Adyen\Payment\Helper\AdyenOrderPayment;
use Adyen\Payment\Helper\ChargedCurrency;
use Adyen\Payment\Helper\Config;
use Adyen\Payment\Helper\Data;
use Adyen\Payment\Helper\PaymentMethods;
use Adyen\Payment\Logger\AdyenLogger;
use Adyen\Payment\Model\Config\Source\Status\AdyenState;
use Adyen\Payment\Model\Notification;
use Adyen\Payment\Model\ResourceModel\Order\Payment\CollectionFactory as OrderPaymentCollectionFactory;
use Adyen\Payment\Model\ResourceModel\Creditmemo\Creditmemo as AdyenCreditMemoResourceModel;
use Adyen\Payment\Helper\Creditmemo as AdyenCreditmemoHelper;
use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\NotifierPool;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;
use Adyen\Payment\Helper\Webhook;

class Order extends \Adyen\Payment\Helper\Order
{

    private $transactionBuilder;

    /** @var Data */
    private $dataHelper;

    /** @var AdyenLogger */
    private $adyenLogger;

    /** @var OrderSender */
    private $orderSender;

    /** @var TransactionFactory */
    private $transactionFactory;

    /** @var ChargedCurrency */
    private $chargedCurrency;

    /** @var AdyenOrderPayment */
    private $adyenOrderPaymentHelper;

    /** @var Config */
    private $configHelper;

    /** @var OrderStatusCollectionFactory */
    private $orderStatusCollectionFactory;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var OrderRepository  */
    private $orderRepository;

    /** @var NotifierPool */
    private $notifierPool;

    /** @var OrderPaymentCollectionFactory */
    private $adyenOrderPaymentCollectionFactory;

    /** @var PaymentMethods */
    private $paymentMethodsHelper;

    /** @var AdyenCreditMemoResourceModel */
    private $adyenCreditmemoResourceModel;

    /** @var AdyenCreditmemoHelper */
    private $adyenCreditmemoHelper;

    public function __construct(
        Context $context,
        Builder $transactionBuilder,
        Data $dataHelper,
        AdyenLogger $adyenLogger,
        OrderSender $orderSender,
        TransactionFactory $transactionFactory,
        ChargedCurrency $chargedCurrency,
        AdyenOrderPayment $adyenOrderPaymentHelper,
        Config $configHelper,
        OrderStatusCollectionFactory $orderStatusCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepository $orderRepository,
        NotifierPool $notifierPool,
        OrderPaymentCollectionFactory $adyenOrderPaymentCollectionFactory,
        PaymentMethods $paymentMethodsHelper,
        AdyenCreditMemoResourceModel $adyenCreditmemoResourceModel,
        AdyenCreditmemoHelper $adyenCreditmemoHelper
    ) {
        parent::__construct(
            $context,
            $transactionBuilder,
            $dataHelper,
            $adyenLogger,
            $orderSender,
            $transactionFactory,
            $chargedCurrency,
            $adyenOrderPaymentHelper,
            $configHelper,
            $orderStatusCollectionFactory,
            $searchCriteriaBuilder,
            $orderRepository,
            $notifierPool,
            $adyenOrderPaymentCollectionFactory,
            $paymentMethodsHelper,
            $adyenCreditmemoResourceModel,
            $adyenCreditmemoHelper
        );
        $this->transactionBuilder = $transactionBuilder;
        $this->dataHelper = $dataHelper;
        $this->adyenLogger = $adyenLogger;
        $this->orderSender = $orderSender;
        $this->transactionFactory = $transactionFactory;
        $this->chargedCurrency = $chargedCurrency;
        $this->adyenOrderPaymentHelper = $adyenOrderPaymentHelper;
        $this->configHelper = $configHelper;
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->notifierPool = $notifierPool;
        $this->adyenOrderPaymentCollectionFactory = $adyenOrderPaymentCollectionFactory;
        $this->paymentMethodsHelper = $paymentMethodsHelper;
        $this->adyenCreditmemoResourceModel = $adyenCreditmemoResourceModel;
        $this->adyenCreditmemoHelper = $adyenCreditmemoHelper;
    }

    public function finalizeOrder(MagentoOrder $order, Notification $notification): MagentoOrder
    {
        $amount = $notification->getAmountValue();
        $orderAmountCurrency = $this->chargedCurrency->getOrderAmountCurrency($order, false);
        $formattedOrderAmount = $this->dataHelper->formatAmount($orderAmountCurrency->getAmount(), $orderAmountCurrency->getCurrencyCode());
        $fullAmountFinalized = $this->adyenOrderPaymentHelper->isFullAmountFinalized($order);

        $eventLabel = 'payment_authorized';
        $status = $this->configHelper->getConfigData(
            $eventLabel,
            'adyen_abstract',
            $order->getStoreId()
        );
        $possibleStates = Webhook::STATE_TRANSITION_MATRIX[$eventLabel];

        // Set state back to previous state to prevent update if 'maintain status' was configured
        $maintainingState = false;
        if ($status === AdyenState::STATE_MAINTAIN) {
            $maintainingState = true;
            $status = $order->getStatus();
        }

        // virtual order can have different statuses
        if ($order->getIsVirtual()) {
            $status = $this->getVirtualStatus($order, $status);
        }

        // check for boleto if payment is totally paid
        if ($order->getPayment()->getMethod() == "adyen_boleto") {
            $status = $this->paymentMethodsHelper->getBoletoStatus($order, $notification, $status);
        }

        $order = $this->addProcessedStatusHistoryComment($order, $notification);
        if ($fullAmountFinalized) {
            $this->adyenLogger->addAdyenNotification(
                sprintf(
                    'Notification w/amount %s has completed the capturing of order %s w/amount %s',
                    $amount,
                    $order->getIncrementId(),
                    $formattedOrderAmount
                ),
                [
                    'pspReference' => $notification->getPspreference(),
                    'merchantReference' => $notification->getMerchantReference()
                ]
            );
            $comment = "Adyen Payment Successfully completed";
            // If a status is set, add comment, set status and update the state based on the status
            // Else add comment
            if (!empty($status) && $maintainingState) {
                $order->addStatusHistoryComment(__($comment), $status);
                $this->adyenLogger->addAdyenNotification(
                    'Maintaining current status: ' . $status,
                    array_merge(
                        $this->adyenLogger->getOrderContext($order),
                        ['pspReference' => $notification->getPspreference()]
                    )
                );
            } elseif (!empty($status)) {
                $order->addStatusHistoryComment(__($comment), $status);
                $this->setState($order, $status, $possibleStates);
                $this->adyenLogger->addAdyenNotification(
                    'Order status was changed to authorised status: ' . $status,
                    array_merge(
                        $this->adyenLogger->getOrderContext($order),
                        ['pspReference' => $notification->getPspreference()]
                    )
                );
            } else {
                $order->addStatusHistoryComment(__($comment));
                $this->adyenLogger->addAdyenNotification(
                    sprintf(
                        'Order %s was finalized. Authorised status not set',
                        $order->getIncrementId()
                    ),
                    [
                        'pspReference' => $notification->getPspreference(),
                        'merchantReference' => $notification->getMerchantReference()
                    ]
                );
            }

            if ($order->getIsPreOrder()) {
                $order->setState(MagentoOrder::STATE_PROCESSING);
                $order->setStatus("pre_order_processing");
                $this->adyenLogger->addAdyenNotification(
                    'Order status was changed to: pre_order_processing. OrderID: ' . $order->getIncrementId(),
                    array_merge(
                        $this->adyenLogger->getOrderContext($order),
                        ['pspReference' => $notification->getPspreference()]
                    )
                );
            }
        }

        return $order;
    }

    private function getVirtualStatus(MagentoOrder $order, $status)
    {
        $this->adyenLogger->addAdyenNotification(
            'Product is a virtual product',
            [
                'pspReference' => $order->getPayment()->getData('adyen_psp_reference'),
                'merchantReference' => $order->getPayment()->getData('entity_id')
            ]
        );
        $virtualStatus = $this->configHelper->getConfigData(
            'payment_authorized_virtual',
            'adyen_abstract',
            $order->getStoreId()
        );
        if ($virtualStatus != "") {
            $status = $virtualStatus;
        }

        return $status;
    }

    private function setState(MagentoOrder $order, $status, $possibleStates): MagentoOrder
    {
        // Loop over possible states, select first available status that fits this state
        foreach ($possibleStates as $state) {
            $statusObject = $this->orderStatusCollectionFactory->create()
                ->addFieldToFilter('main_table.status', $status)
                ->joinStates()
                ->addStateFilter($state)
                ->getFirstItem();

            if ($statusObject->getState() == $state) {
                // Exit function if fitting state is found
                $order->setState($statusObject->getState());
                $this->adyenLogger->addAdyenNotification(
                    'State is changed to ' . $statusObject->getState(),
                    [
                        'pspReference' => $order->getPayment()->getData('adyen_psp_reference'),
                        'merchantReference' => $order->getPayment()->getData('entity_id')
                    ]
                );

                return $order;
            }
        }

        $this->adyenLogger->addAdyenNotification(
            'No new state assigned, status should be connected to one of the following states: ' . json_encode($possibleStates),
            [
                'pspReference' => $order->getPayment()->getData('adyen_psp_reference'),
                'merchantReference' => $order->getPayment()->getData('entity_id')
            ]
        );

        return $order;
    }
}
