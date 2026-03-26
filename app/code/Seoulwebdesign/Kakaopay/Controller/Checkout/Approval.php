<?php

namespace Seoulwebdesign\Kakaopay\Controller\Checkout;

use Seoulwebdesign\Kakaopay\Helper\Constant;
use Seoulwebdesign\Kakaopay\Model\Ui\ConfigProvider;

class Approval extends \Seoulwebdesign\Kakaopay\Controller\Checkout
{

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $this->logger->debug('Approval-param-' . print_r($params, true));

        $order = $this->checkoutSession->getLastRealOrder();
        $this->logger->debug('Order: ' . print_r($this->checkoutSession->getData(), true));
        if (!$order || !$order->getId()) {
            $order = $this->orderProcessing->getOrderByIncrementId($params['oid']);
        }
        if (!$order || !$order->getId()) {
            $this->logger->debug('No order not found');
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        if (!$payment) {
            $order = $this->orderFactory->create()->load($order->getId());
            $payment = $order->getPayment();

            if (!$payment) {
                $paymentCollection = $order->getPaymentsCollection();
                if ($paymentCollection->getSize() > 0) {
                    $payment = $paymentCollection->getFirstItem();
                }
            }
        }

        if (!$payment) {
            throw new \Magento\Framework\Exception\LocalizedException(__("No payment found for this order " . $order->getId()));
        }
        $tid = $payment->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_TID);
        $dataCheck = [
            'cid' => $this->configHelper->getCID(),
            'tid' => $tid
        ];
        $responseCheck = $this->configHelper->sendCurl(Constant::KAKAOPAY_PAYMENT_CHECK, $dataCheck, "POST");
        /*
         (
            [tid] => T2928260311251793012
            [cid] => C626420035
            [status] => AUTH_PASSWORD
            [partner_order_id] => 000000422
            [partner_user_id] => mods2003@gmail.com
            [payment_method_type] => MONEY
            [item_name] => 결제 테스트  Payment Test -1
            [item_code] => 000000422
            [quantity] => 0
            [amount] => Array
                (
                    [total] => 1000
                    [tax_free] => 0
                    [vat] => 0
                )

            [cancel_available_amount] => Array
                (
                    [total] => 1000
                    [tax_free] => 0
                    [vat] => 0
                )

            [canceled_amount] => Array
                (
                    [total] => 0
                    [tax_free] => 0
                    [vat] => 0
                )

            [created_at] => 2021-08-09T01:45:34
        )
         */
        $this->logger->debug(print_r($responseCheck, true));
        if ($responseCheck && isset($responseCheck['status']) && $responseCheck['status'] == 'AUTH_PASSWORD') {
            $tid =  $responseCheck['tid'];
            try {
                $commandExecutor = $this->commandManagerPool->get(ConfigProvider::CODE);
                $token = $params['pg_token'];
                $commandExecutor->executeByCode(
                    'capture',
                    $order->getPayment(),
                    [
                        'cid' => strval($this->configHelper->getCID()),
                        'tid' => $tid,
                        'partner_order_id' => $responseCheck['partner_order_id'],
                        'partner_user_id' =>  $responseCheck['partner_user_id'],
                        'pg_token' => $token,
                    ]
                );

                return $this->_redirect('checkout/onepage/success');
            } catch (\Throwable $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                $this->logger->debug('Approval-' . $exception->getMessage());
                return $this->_redirect('checkout/cart');
            }
        } else {
            return $this->_redirect('');
        }
    }
}
