<?php

namespace Cpss\Crm\Model\Btoc;

use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Api\Btoc\TokenInterface;
use Cpss\Crm\Helper\MemberValidation;
use Cpss\Crm\Helper\Customer;

class Token implements TokenInterface
{
    /**
     * @var Customer
     */
    protected $crmHelper;

    /**
     * @var MemberValidation
     */
    protected $validation;

    public function __construct(
        Customer $crmHelper,
        MemberValidation $validation
    ) {
        $this->crmHelper = $crmHelper;
        $this->validation = $validation;
    }

    public function checkToken()
    {
        header('Content-Type: application/json');
        $response = [];

        try {
            $data = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("INFO", $data);
            $success = Result::SUCCESS;
            $resultCode = $this->validation->validateParams(Param::CHECK_TOKEN, $data);

            if ($resultCode === $success) {
                $websiteId  = $this->crmHelper->getWebsiteId();
                $customer  = $this->crmHelper->getCustomerFactory();
                $customer->setWebsiteId($websiteId);
                $customer->load($data['memberId']);

                if ($customer->getId()) {
                    $resultCode = $this->crmHelper->validateToken($customer->getPasswordHash(), $data[Param::ACCESS_TOKEN]);
                } else {
                    $resultCode = Result::ACCESS_DENIED;
                }
            }

            $resultExplanation = Result::RESULT_CODES[$resultCode];

            $response = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ];
        } catch (\Exception $e) {
            $this->crmHelper->logCritical($e->getMessage());
            $resultCode = Result::INTERNAL_ERROR;
            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $response = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ];
        }
        $response = $this->crmHelper->convertArrayValuesToString($response);
        echo json_encode($response);
        exit();
    }
}
