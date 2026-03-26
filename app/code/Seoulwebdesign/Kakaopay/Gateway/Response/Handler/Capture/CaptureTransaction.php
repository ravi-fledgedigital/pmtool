<?php

namespace Seoulwebdesign\Kakaopay\Gateway\Response\Handler\Capture;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment as PaymentResource;
use Psr\Log\LoggerInterface;
use Seoulwebdesign\Kakaopay\Helper\Constant;

class CaptureTransaction implements HandlerInterface
{
    /***
     * @var PaymentResource
     */
    protected $paymentResource;

    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CaptureTransaction constructor.
     * @param PaymentResource $paymentResource
     * @param BuilderInterface $transactionBuilder
     * @param OrderFactory $orderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentResource $paymentResource,
        BuilderInterface $transactionBuilder,
        OrderFactory $orderFactory,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->transactionBuilder = $transactionBuilder;
        $this->paymentResource = $paymentResource;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        try {
            $payment = $handlingSubject['payment']->getPayment();
            $order = $payment->getOrder();
            /** @var $order \Magento\Sales\Model\Order */
            $formattedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );
            foreach ($response['object'] as $key => $value) {
                if (is_array($value)) {
                    $response['object'][$key] = json_encode($value);
                }
            }

            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($response['object']['tid'])
                ->setAdditionalInformation(
                    [Transaction::RAW_DETAILS => $response['object']]
                )
                ->setFailSafe(true)
                ->build(TransactionInterface::TYPE_CAPTURE);
            $payment->setAmountAuthorized($order->getGrandTotal());
            //$payment->setCcNumberEnc($response['CARD_Num']);
            $payment->addTransactionCommentsToOrder(
                $transaction,
                __('The Payment Capture success. Captured amount is %1.', $formattedPrice)
            );

            //$payment->setAdditionalInformation(Constant::KAKAOPAY_RESPONSE_TOKEN, $response['pg_token']);
            $payment->setAdditionalInformation(Constant::KAKAOPAY_RESPONSE_POI, $response['object']['partner_order_id']);
            $payment->setAdditionalInformation(Constant::KAKAOPAY_RESPONSE_PUI, $response['object']['partner_user_id']);
            $payment->setAdditionalInformation(Constant::KAKAOPAY_RESPONSE_PAYMENT_DETAIL, $response['object']);

            $payment->setParentTransactionId(null);
            $transaction->save();
            $order->addStatusToHistory($order->getStatus(), 'Payment Captured.');
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
