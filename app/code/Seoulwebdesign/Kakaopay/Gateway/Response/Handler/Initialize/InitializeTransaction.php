<?php

namespace Seoulwebdesign\Kakaopay\Gateway\Response\Handler\Initialize;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Psr\Log\LoggerInterface;
use Seoulwebdesign\Base\Helper\MobileDetect;
use Seoulwebdesign\Kakaopay\Helper\Constant;

/**
 * Class AuthTransaction
 */
class InitializeTransaction implements HandlerInterface
{
    /**
     * @var BuilderInterface
     */
    private $transactionBuilder;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var MobileDetect
     */
    private $mobileDetect;

    /**
     * AuthTransaction constructor.
     * @param BuilderInterface $transactionBuilder
     * @param MobileDetect $mobileDetect
     * @param LoggerInterface $logger
     */
    public function __construct(
        BuilderInterface $transactionBuilder,
        MobileDetect $mobileDetect,
        LoggerInterface $logger
    ) {
        $this->transactionBuilder = $transactionBuilder;
        $this->mobileDetect = $mobileDetect;
        $this->logger = $logger;
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
//                $transaction = $this->transactionBuilder->setPayment($payment)
//                    ->setOrder($order)
//                    ->setTransactionId($response['object']['tid'])
//                    ->setAdditionalInformation(
//                        [PaymentTransaction::RAW_DETAILS => $response['object']]
//                    )
//                    ->setFailSafe(true)
//                    ->build(TransactionInterface::TYPE_AUTH);
                $payment->setIsTransactionPending(true);
                $payment->setAmountAuthorized($order->getGrandTotal());
                //$payment->setCcNumberEnc($response['CARD_Num']);
//                $payment->addTransactionCommentsToOrder(
//                    $transaction,
//                    __('The Payment Authorize success. Authorized amount is %1.', $formattedPrice)
//                );

                $payment->setAdditionalInformation(
                    Constant::KAKAOPAY_MOBILE_RESPONSE_URL,
                    $response['object']['next_redirect_mobile_url']
                );
                $payment->setAdditionalInformation(
                    Constant::KAKAOPAY_RESPONSE_URL,
                    $response['object']['next_redirect_pc_url']
                );
                $payment->setAdditionalInformation(Constant::KAKAOPAY_RESPONSE_TID, $response['object']['tid']);

                //$transaction->save();
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
