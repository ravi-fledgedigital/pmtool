<?php

namespace Cpss\Crm\Model\Shop;

use Cpss\Crm\Api\Shop\LoginInterface;
use Cpss\Crm\Helper\Validation;
use Cpss\Crm\Model\RealStoreFactory as RealStore;
use Cpss\Crm\Model\Shop\Config\Param;
use Cpss\Crm\Model\Shop\Config\Result;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Integration\Api\AdminTokenServiceInterface;

class Login implements LoginInterface
{
    protected $requestInterface;
    protected $validationHelper;
    protected $encryptor;
    protected $realStoreFactory;

    public function __construct(
        AdminTokenServiceInterface $adminTokenServiceInterface,
        RequestInterface $requestInterface,
        Validation $validationHelper,
        Encryptor $encryptor,
        RealStore $realStoreFactory
    ) {
        $this->adminTokenService = $adminTokenServiceInterface;
        $this->requestInterface = $requestInterface;
        $this->validationHelper = $validationHelper;
        $this->encryptor = $encryptor;
        $this->realStoreFactory = $realStoreFactory;
    }

    public function loginShop()
    {
        $params = $this->requestInterface->getParams();
        try {
            $response = [];
            $resultCode = Result::SUCCESS;
            $msg = "";

            $resultCode = $this->validationHelper->validateParams(Param::SHOP_LOGIN_SHOP_PARAMS, $params);
            // if params has no value return Invalid Param
            if (empty($params[Param::SITE_ID]) || empty($params[Param::SHOP_ID]) || empty($params[Param::SHOP_PASS])) {
                $resultCode = Result::INVALID_PARAMS;
            } else {
                $resultCode = ($resultCode == Result::SUCCESS
                    && $store = $this->login($params[Param::SHOP_ID], $params[Param::SHOP_PASS]))
                    ? Result::SUCCESS : Result::AUTH_FAILED;
            }

            if ($resultCode == Result::SUCCESS) {
                $response = [
                    'resultCode' => $resultCode,
                    'resultExplanation' => Result::RESULT_CODES[$resultCode],
                    'shopId' => $params[Param::SHOP_ID],
                    'shopName' => $store->getShopName(),
                    'accessToken' => $store->getAccessToken()
                ];
            } else {
                $response = [
                    'resultCode' => $resultCode,
                    'resultExplanation' => Result::RESULT_CODES[$resultCode],
                    'message' => $msg
                ];
            }
        } catch (\Exception $e) {
            $response = ['resultCode' => Result::INTERNAL_ERROR, 'resultExplanation' => Result::RESULT_CODES[Result::INTERNAL_ERROR], 'message' => $e->getMessage()];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }

    /**
     * Generate Access Token
     *
     * @return void
     */
    private function generateAccessToken()
    {
        $accessToken = $this->encryptor->getHash($this->shopPass(), $this->salt());
        $accessToken = explode(Encryptor::DELIMITER, $accessToken);
        $accessToken = $accessToken[0];
        return $accessToken;
    }

    private function salt()
    {
        return $this->validationHelper->getSalt();
    }

    private function shopPass()
    {
        return $this->validationHelper->getShopPass();
    }

    private function login($shopId, $password)
    {
        if (empty($shopId) || empty($password)) {
            return false;
        }

        return $this->realStoreFactory->create()->login($shopId, $password);
    }
}
