<?php

namespace Cpss\Crm\Model\Btoc;

use Cpss\Crm\Helper\Customer;
use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Helper\MemberValidation;
use Magento\Newsletter\Model\Subscriber;

class Info implements \Cpss\Crm\Api\Btoc\InfoInterface
{
    protected $crmHelper;
    protected $validation;
    protected $subscriber;

    public function __construct(
        Customer $crmHelper,
        MemberValidation $validation,
        Subscriber $subscriber
    ) {
        $this->crmHelper = $crmHelper;
        $this->validation = $validation;
        $this->subscriber = $subscriber;
    }

    public function getMemberInfo()
    {
        header('Content-Type: application/json');
        $response = [];
        try {
            $data = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("INFO", $data);
            $success = Result::SUCCESS;
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
                $postalCode = $telephone = $countyCode = $prefecture = "";
                $address = [];
                if ($customer->getDefaultBillingAddress()) {
                    $postalCode = explode('-', $customer->getDefaultBillingAddress()->getPostcode());
                    $telephone = explode('-', $customer->getDefaultBillingAddress()->getTelephone());
                    $countyCode = $customer->getDefaultBillingAddress()->getCountry();
                    $prefecture = $customer->getDefaultBillingAddress()->getRegion();
                    $address = $customer->getDefaultBillingAddress()->getStreet();
                }
                $dob = $customer->getDob() != null ? date('Ymd', strtotime($customer->getDob())) : "";
                $subscriberStatus = $this->subscriber->loadBySubscriberEmail($customer->getEmail(), 1);

                $response = [
                    'email' => $customer->getEmail(),
                    'lastName' => $customer->getLastname(),
                    'firstName' => $customer->getFirstname(),
                    'lastNameKana' => $customer->getLastnameKana() != "　" ? $customer->getLastnameKana() : "",
                    'firstNameKana' => $customer->getFirstnameKana() != "　" ? $customer->getFirstnameKana() : "",
                    'gender' => $customer->getGender() != null ? $this->crmHelper->getGender((int)$customer->getGender(), false) : "",
                    'birthDay' => $dob,
                    'countryCode' => $countyCode,
                    'postalCode1' => isset($postalCode[0]) ? $postalCode[0] : '',
                    'postalCode2' => isset($postalCode[1]) ? $postalCode[1] : '',
                    'prefecture' => $prefecture,
                    'address1' => isset($address[0]) ? ($address[0] != "　" ? $address[0] : '') : '',
                    'address2' => isset($address[1]) ? $address[1] : '',
                    'phone1' => isset($telephone[0]) ? $telephone[0] : '',
                    'phone2' => isset($telephone[1]) ? $telephone[1] : '',
                    'phone3' => isset($telephone[2]) ? $telephone[2] : '',
                    'subscribeNewsLetter' => ($subscriberStatus->getStatus()) ? 1 : 0,
                    'occupation' => ($customer->getOccupation()) ? $customer->getOccupation() : ""
                ];
            }

            $resultExplanation = Result::RESULT_CODES[$resultCode];

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
        $response = $this->crmHelper->convertArrayValuesToString($response);

        echo json_encode($response);
        exit();
    }
}
