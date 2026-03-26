<?php

namespace OnitsukaTigerCpss\Crm\Model\Shop;

use Cpss\Crm\Logger\Logger;
use Cpss\Crm\Model\Btoc\Config\Result as CrmResult;
use Cpss\Crm\Model\RealStoreFactory as RealStore;
use Cpss\Crm\Model\Shop\Config\Result;
use Cpss\Crm\Model\Shop\Login as CrmLogin;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\Store\Model\Website;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;
use OnitsukaTigerCpss\Crm\Helper\Validation;
use OnitsukaTigerCpss\Crm\Model\Shop\Config\Param;

class Login extends CrmLogin
{
    protected $adminTokenService;
    protected $requestInterface;
    protected $validationHelper;
    protected $encryptor;
    protected $realStoreFactory;
    protected Website $website;
    protected $logger;

    public function __construct(
        AdminTokenServiceInterface $adminTokenServiceInterface,
        RequestInterface           $requestInterface,
        Validation                 $validationHelper,
        Encryptor                  $encryptor,
        RealStore                  $realStoreFactory,
        Website                    $website,
        Logger                     $logger
    ) {
        $this->adminTokenService = $adminTokenServiceInterface;
        $this->requestInterface = $requestInterface;
        $this->validationHelper = $validationHelper;
        $this->encryptor = $encryptor;
        $this->realStoreFactory = $realStoreFactory;
        $this->website = $website;
        $this->logger = $logger;
        parent::__construct($adminTokenServiceInterface, $requestInterface, $validationHelper, $encryptor, $realStoreFactory);
    }

    public function loginShop()
    {
        $params = $this->requestInterface->getParams();
        $response = [];
        $resultCode = Result::SUCCESS;
        try {
            $msg = "";
            $website = false;
            if (isset($params[Param::SITE_ID])) {
                $website = $this->website->load($params[Param::SITE_ID]);
            }

            if ($website && $website->getId() && $website->getCode() == 'web_kr') {
                $resultCode = $this->validationHelper->validateParams(Param::KR_REQUEST_SHOP_LOGIN_SHOP_PARAMS, $params);
            } else {
                $resultCode = $this->validationHelper->validateParams(Param::REQUEST_SHOP_LOGIN_SHOP_PARAMS, $params);
            }

            //validate for SG
            /*if (!$website->getId() ||
                ( !empty($website->getId()) &&MemberValidation::WEBSITE_COUNTRY_CODE[$website->getId()] != "SG"))  {*/
            if (!$website || !$website->getId()) {
                $resultCode = CrmResult::ACCESS_DENIED;
                $this->logger->critical(__('Website is not found or %1', CrmResult::RESULT_CODES[$resultCode]));
                $response = [
                    'resultCode' => $resultCode,
                    'resultExplanation' => CrmResult::RESULT_CODES[$resultCode]
                ];
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($response);
                exit;
            }

            if ($resultCode == Result::SUCCESS) {
                //validate for SG
                /*if (!$website->getId() ||
                    (!empty($website->getId()) &&MemberValidation::WEBSITE_COUNTRY_CODE[$website->getId()] !== "SG")) {*/
                if (!$website || !$website->getId()) {
                    $resultCode = Result::AUTH_FAILED;
                    $this->logger->critical(__('Website is not found or %1', Result::RESULT_CODES[$resultCode]));
                    $response = [
                        'resultCode' => $resultCode,
                        'resultExplanation' => Result::RESULT_CODES[$resultCode]
                    ];
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($response);
                    exit;
                }

                $store = $this->login($params[Param::SHOP_ID], $params[Param::SHOP_PASS]);

                if ($store && $store->getId()) {
                    $countryCode = (!empty($store->getCountryCode())) ? strtoupper($store->getCountryCode()) : 'SG';
                    $websiteId = array_search($countryCode, MemberValidation::WEBSITE_COUNTRY_CODE);
                    if ($websiteId != $params[Param::SITE_ID]) {
                        $resultCode = Result::AUTH_FAILED;
                        $this->logger->critical(__('Website is not found or %1', Result::RESULT_CODES[$resultCode]));
                        $response = [
                            'resultCode' => $resultCode,
                            'resultExplanation' => Result::RESULT_CODES[$resultCode]
                        ];
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode($response);
                        exit;
                    }
                }

                $resultCode = $store ? Result::SUCCESS : Result::AUTH_FAILED;
                $response = [
                    'resultCode' => $resultCode,
                    'resultExplanation' => Result::RESULT_CODES[$resultCode],
                    'shopId' => $params[Param::SHOP_ID],
                    'shopName' => $store ? $store->getShopName() : "",
                    'accessToken' => $store ? $store->getAccessToken() : ""
                ];
                if ($resultCode == Result::AUTH_FAILED) {
                    $response = [
                        'resultCode' => $resultCode,
                        'resultExplanation' => Result::RESULT_CODES[$resultCode],
                    ];
                }
            } else {
                $response = [
                    'resultCode' => $resultCode,
                    'resultExplanation' => Result::RESULT_CODES[$resultCode]
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'resultCode' => Result::INTERNAL_ERROR,
                'resultExplanation' => Result::RESULT_CODES[Result::INTERNAL_ERROR],
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }

    /**
     * @return string|void
     */
    private function generateAccessToken()
    {
        $accessToken = $this->encryptor->getHash($this->shopPass(), $this->salt());
        $accessToken = explode(Encryptor::DELIMITER, $accessToken);
        return $accessToken[0];
    }

    private function salt()
    {
        return $this->validationHelper->getSalt();
    }

    private function shopPass()
    {
        return $this->validationHelper->getShopPass();
    }

    /**
     * @param $shopId
     * @param $password
     * @return \Cpss\Crm\Model\RealStore|false|null
     */
    private function login($shopId, $password)
    {
        return $this->realStoreFactory->create()->login($shopId, $password);
    }
}
