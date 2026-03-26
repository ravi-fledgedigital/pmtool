<?php

namespace Seoulwebdesign\Kakaopay\Controller\Checkout;

use Magento\Framework\Controller\ResultFactory;
use Seoulwebdesign\Kakaopay\Helper\Constant;

class Redirect extends \Seoulwebdesign\Kakaopay\Controller\Checkout
{
    public function execute()
    {
        $result = $this->jsonFactory->create();
        if ($this->getRequest()->isAjax()) {
            try {
                $order = $this->checkoutSession->getLastRealOrder();
                if ($this->mobileDetect->isMobile()) {
                    $payUrl = $order->getPayment()->getAdditionalInformation(Constant::KAKAOPAY_MOBILE_RESPONSE_URL);
                } else {
                    $payUrl = $order->getPayment()->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_URL);
                }
                if (!!$payUrl) {
                    return $result->setData([
                        'payUrl' => $payUrl,
                        'success' => true,
                        'error' => false
                    ]);
                } else {
                    return $result->setData([
                        'success' => false,
                        'error' => true,
                        'errorMessage' => "Pay Url can't created ! Please try again"
                    ]);
                }
            } catch (\Exception $e) {
                if ($this->configHelper->getCanDebug()) {
                    $this->logger->debug($e->getMessage());
                }
                return $this->jsonFactory->create()->setData([
                    'success' => false,
                    'error' => true,
                    'errorMessage' => $e->getMessage()
                ]);
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('');
        return $resultRedirect;
    }
}
