<?php

namespace OnitsukaTigerCpss\Crm\Model\Btoc;

use Cpss\Crm\Helper\MemberValidation;
use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Model\CpssApiRequest;
use Magento\Store\Model\App\Emulation;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelp;

class Delete extends \Cpss\Crm\Model\Btoc\Delete
{
    protected $customerHelper;
    /**
     * @var Emulation
     */
    private $emulation;
    public function __construct(
        CustomerHelp $crmHelper,
        MemberValidation $validation,
        CpssApiRequest $cpssApi,
        Emulation $emulation
    )
    {
        $this->customerHelper = $crmHelper;
        $this->emulation = $emulation;
        parent::__construct($crmHelper, $validation, $cpssApi);
    }

    public function deleteMember()
    {
        header('Content-Type: application/json');
        $response = [];
        try {
            $success = Result::SUCCESS;
            $data = $this->customerHelper->getParamsRequest();
            $this->customerHelper->logDebug("DELETE", $data);
            $resultCode = $this->validation->validateParams(Param::UPDATE_MEMBER_PARAMS, $data);
            $websiteId  = $data[Param::SITE_ID];
            $storeId = $this->customerHelper->getStoreDefaultId($websiteId);
            $this->emulation->startEnvironmentEmulation($storeId);

            if ($resultCode === $success) {

                $customer  = $this->customerHelper->getCustomerFactory();
                $customer->setWebsiteId($websiteId);
                $customer = $customer->load($data['memberId']);
                if ($customer->getId()) {
                    $resultCode = $this->customerHelper->validateToken($customer->getPasswordHash(), $data[Param::ACCESS_TOKEN]);
                } else {
                    $resultCode = Result::ACCESS_DENIED;
                }
            }

            if ($resultCode === $success) {
                $customerId = $customer->getId();
                $customer->delete();
                $this->cpssApi->updateMember($this->customerHelper->getCpssMembeIdPrefix() . $customerId);
            }
            $this->emulation->stopEnvironmentEmulation();
            $resultExplanation = Result::RESULT_CODES[$resultCode];

            $response = array_merge($response, [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ]);
        } catch (\Exception $e) {
            $this->customerHelper->logCritical($e->getMessage());
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
