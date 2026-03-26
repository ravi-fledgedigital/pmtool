<?php

namespace OnitsukaTigerCpss\Crm\Model\Btoc;

use Cpss\Crm\Model\Btoc\Config\Result;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\App\Emulation;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelper;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;
use OnitsukaTigerCpss\Crm\Model\Btoc\Config\Param;

class Info extends \Cpss\Crm\Model\Btoc\Info
{
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
    )
    {
        $this->crmHelper = $crmHelper;
        $this->emulation = $emulation;
        $this->subscriber = $subscriber;
        $this->validation = $validation;
        parent::__construct($crmHelper, $validation, $subscriber);
    }


    public function getMemberInfo()
    {
        header('Content-Type: application/json');
        $response = [];
        try {
            $data = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("INFO", $data);
            $success = Result::SUCCESS;
            $resultCode = $this->validation->validateData(Param::REQUEST_GET_MEMBER_INFO_PARAMS, $data);

            if ($resultCode === $success) {
                $websiteId  = $data[Param::SITE_ID];

                $customer  = $this->crmHelper->getCustomerFactory();
                $customer->setWebsiteId($websiteId);
                $customer->load($data[Param::MEMBER_ID]);

                if ($customer->getId()) {
                    $resultCode = $this->crmHelper->validateToken($customer->getPasswordHash(), $data[Param::ACCESS_TOKEN]);
                } else {
                    $resultCode = Result::ACCESS_DENIED;
                }
            }
            if (!empty($params[Param::SITE_ID])) {
                $storeId = $this->crmHelper->getStoreDefaultId($params[Param::SITE_ID]);
                $this->emulation->startEnvironmentEmulation($storeId, 'frontend', true);
            }
            if ($resultCode === $success) {
                $postalCode = $telephone = $countyCode = $prefecture = "";
                $address = [];
                if ($customer->getDefaultBillingAddress()) {
                    $postalCode = $customer->getDefaultBillingAddress()->getPostcode();
                    $telephone = $customer->getDefaultBillingAddress()->getTelephone();
                    $countyCode = $customer->getDefaultBillingAddress()->getCountry();
                    $prefecture = $customer->getDefaultBillingAddress()->getRegion();
                    $address = $customer->getDefaultBillingAddress()->getStreet();
                }
                $dob = $customer->getDob() != null ? date('Ymd', strtotime($customer->getDob())) : "";
                $subscriberStatus = $this->subscriber->loadBySubscriberEmail($customer->getEmail(), $websiteId);
                $response = [
                    'email' => $customer->getEmail(),
                    'lastName' => $customer->getLastname(),
                    'firstName' => $customer->getFirstname(),
                    'gender' => $customer->getGender(),
                    'birthDay' => $dob,
                    'countryCode' => $countyCode,
                    'postalCode1' => isset($postalCode) ? $postalCode : '',
                    'prefecture' => $prefecture,
                    'address1' => isset($address[0]) ? ($address[0] != "　" ? $address[0] : '') : '',
                    'address2' => isset($address[1]) ? $address[1] : '',
                    'phone1' => !empty($telephone) ? $this->formatTelephone($customer,$telephone): '',
                    'subscribeNewsLetter' => $subscriberStatus->getStatus(),
                    'occupation' => ($customer->getOccupation()) ? $customer->getOccupation() : ""
                ];
            }
            $this->emulation->stopEnvironmentEmulation();
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

    /**
     * @param $customer
     * @param $telephone
     * @return string
     */
    private function formatTelephone($customer, $telephone)
    {
        $telephonePrefix = $this->crmHelper->getTelephoneCountryCode($customer);
        if (empty($telephonePrefix)) {
            return $telephone;
        }

        return substr($telephone,2);
    }
}
