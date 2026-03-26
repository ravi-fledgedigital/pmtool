<?php

namespace OnitsukaTigerCpss\Crm\Model\Btoc;

use Cpss\Crm\Helper\MemberValidation;
use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Model\Btoc\Config\Result;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\App\Emulation;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelper;

class Token
{
    /**
     * @inheritDoc
     */

    protected $crmHelper;
    protected $validation;
    protected $subscriber;
    /**
     * @var Emulation
     */
    private $emulation;
    public function __construct(
        CustomerHelper $crmHelper,
        MemberValidation $validation,
        Subscriber $subscriber,
        Emulation $emulation
    ) {
        $this->crmHelper = $crmHelper;
        $this->validation = $validation;
        $this->subscriber = $subscriber;
        $this->emulation = $emulation;
    }

    public function checkToken()
    {
        header('Content-Type: application/json');
        $response = [];
        try {
            $data = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("Check", $data);

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/checkTokenApi.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('=====Check Token API Log Start=====');

            $resultCode = $this->validation->validateParamsForToBApp(Param::UPDATE_MEMBER_PARAMS, $data);
            $logger->info("Result Code Of validateParamsForToBApp: " . $resultCode);
            $websiteId  = $data[Param::SITE_ID];
            $storeId = $this->crmHelper->getStoreDefaultId($websiteId);
            $this->emulation->startEnvironmentEmulation($storeId);

            if ($resultCode === Result::SUCCESS) {
                $customer  = $this->crmHelper->getCustomerFactory();
                $customer = $customer->load($data['memberId']);
                $logger->info("Member ID: " . $data['memberId']);
                if ($customer->getWebsiteId() !== $websiteId) {
                    $resultCode = Result::ACCESS_DENIED;
                } elseif ($customer->getId()) {
                    $resultCode = $this->crmHelper->validateToken($customer->getPasswordHash(), $data[Param::ACCESS_TOKEN]);
                } else {
                    $resultCode = Result::ACCESS_DENIED;
                }
            }
            $logger->info("Result Code Of validateToken: " . $resultCode);
            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $this->emulation->stopEnvironmentEmulation();
            $response = array_merge([
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ], $response);
        } catch (\Exception $e) {
            $this->crmHelper->logCritical($e->getMessage());
            $resultCode = Result::INTERNAL_ERROR;
            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $response = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ];
        }
        $logger->info("=====Check Token API Log End=====");
        $response = $this->crmHelper->convertArrayValuesToString($response);
        echo json_encode($response);
        exit();
    }
}
