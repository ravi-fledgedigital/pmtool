<?php

namespace Seoulwebdesign\Kakaopay\Controller\Checkout;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Seoulwebdesign\Kakaopay\Helper\Constant;

class Cancel extends \Seoulwebdesign\Kakaopay\Controller\Checkout
{
    public function execute()
    {

        try {
            $params = $this->getRequest()->getParams();
            $this->logger->debug('Cancel-param-' . print_r($params, true));
            $order = $this->checkoutSession->getLastRealOrder();
            if (!$order || !$order->getId()) {
                $order = $this->orderProcessing->getOrderByIncrementId($params['oid']);
            }
            if (!$order || !$order->getId()) {
                $this->logger->debug('No order not found');
            }
            $payment = $order->getPayment();
            if (!$payment) {
                throw new LocalizedException(__("No payment found for this order"));
            }
            $dataCheck = [
                'cid' => $this->configHelper->getCID(),
                'tid' => $payment->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_TID)
            ];
            $responseCheck = $this->configHelper->sendCurl(Constant::KAKAOPAY_PAYMENT_CHECK, $dataCheck, "POST");
            if ($responseCheck && isset($responseCheck['status']) && $responseCheck['status'] == 'QUIT_PAYMENT') {
                $order->cancel();
                $order->addCommentToStatusHistory(__('Canceled By Customer'));
                $order->save();
                $this->checkoutSession->restoreQuote();
                $this->messageManager->addErrorMessage(__("You canceled the payment. The order is canceled"));
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout/cart');
            } else {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('');
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__("Something went wrong. Please try again"));
            $this->logger->debug('Cancel-' . $exception->getMessage());
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout/cart');
        }
    }
}
