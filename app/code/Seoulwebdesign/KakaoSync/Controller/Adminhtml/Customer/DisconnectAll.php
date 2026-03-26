<?php
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Controller\Adminhtml\Customer;

use \Seoulwebdesign\KakaoSync\Controller\Adminhtml\Customer;

class DisconnectAll extends Customer
{

    /**
     * DisconnectAll action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response['successMessage']  = 'All token disconnected';
        try {
            $allToken = $this->accessTokenRepository->getAllToken();
            foreach ($allToken as $token) {
                try {
                    $this->kakaoService->unlink($token->getAccessToken());
                } catch (\Throwable $t) {
                    continue;
                }
            }
        } catch (\Throwable $t) {
            $response['errorMessage'] = $t->getMessage();
        }
        $resultJson = $this->resultJsonFactory->create();
        //$response['successMessage'] = 'Success';
        return $resultJson->setData($response);
    }
}
