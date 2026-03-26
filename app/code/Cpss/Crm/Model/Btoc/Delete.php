<?php

namespace Cpss\Crm\Model\Btoc;

use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Helper\Customer;
use Cpss\Crm\Helper\MemberValidation;
use Cpss\Crm\Model\CpssApiRequest;

class Delete implements \Cpss\Crm\Api\Btoc\DeleteInterface
{
    protected $crmHelper;
    protected $validation;
    protected $cpssApi;

    public function __construct(
        Customer $crmHelper,
        MemberValidation $validation,
        CpssApiRequest $cpssApi
    ) {
        $this->crmHelper = $crmHelper;
        $this->validation = $validation;
        $this->cpssApi = $cpssApi;
    }

    public function deleteMember()
    {
        header('Content-Type: application/json');
        $response = [];
        try {
            $success = Result::SUCCESS;
            $data = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("DELETE", $data);
            $resultCode = $this->validation->validateParams(Param::UPDATE_MEMBER_PARAMS, $data);
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

            if ($resultCode === $success) {
                $customerId = $customer->getId();
                $customer->delete();
                $this->cpssApi->updateMember($this->crmHelper->getCpssMembeIdPrefix() . $customerId);
            }

            $resultExplanation = Result::RESULT_CODES[$resultCode];

            $response = array_merge($response, [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ]);
        } catch (\Exception $e) {
            $this->crmHelper->logCritical($e->getMessage());
            $resultCode = Result::INTERNAL_ERROR;
            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $response = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ];
        }
        echo json_encode($response);
        exit();
    }
}
