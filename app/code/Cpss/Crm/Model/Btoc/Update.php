<?php

namespace Cpss\Crm\Model\Btoc;

use Magento\Customer\Model\AddressFactory;
use Cpss\Crm\Helper\Customer;
use Cpss\Crm\Model\Btoc\Config\Param;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Helper\MemberValidation;

class Update implements \Cpss\Crm\Api\Btoc\UpdateInterface
{
    protected $addressFactory;
    protected $crmHelper;
    protected $validation;
    protected $customerResource;
    protected $subscriptionManager;

    public function __construct(
        AddressFactory $addressFactory,
        Customer $crmHelper,
        MemberValidation $validation,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\Newsletter\Model\SubscriptionManagerInterface $subscriptionManager
    ) {
        $this->addressFactory = $addressFactory;
        $this->crmHelper = $crmHelper;
        $this->validation = $validation;
        $this->customerResource = $customerResource;
        $this->subscriptionManager = $subscriptionManager;
    }

    public function updateMember()
    {
        header('Content-Type: application/json');
        $result = [];
        try {
            $success = Result::SUCCESS;
            $params = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("UPDATE", $params);
            $resultCode = $this->validation->validateParams(Param::UPDATE_MEMBER_PARAMS, $params);
            if ($resultCode === $success) {
                $customer = $this->crmHelper->getCustomerFactory()->load($params[Param::MEMBER_ID]);
                if ($customer->getId()) {
                    $resultCode = $this->crmHelper->auth($customer->getPasswordHash(), $params[Param::ACCESS_TOKEN]);
                } else {
                    $resultCode = Result::ACCESS_DENIED;
                }
            }

            if ($resultCode === $success) {
                $customerData = $this->mapCustomerData($params, $customer);
                $websiteId  = $this->crmHelper->getWebsiteId();
                $storeId = $this->crmHelper->getStoreId();
                $isPasswordChanged = false;
                $password = "";
                $email = isset($customerData['email']) ? $customerData['email'] : $customer->getEmail();
                $newsletterSubscriptionStatus = (isset($params['subscribeNewsLetter']) && in_array($params['subscribeNewsLetter'], [0, 1])) ? $params['subscribeNewsLetter'] : null;
                if (!empty($customerData)) {
                    $customer->setWebsiteId($websiteId);
                    foreach ($customerData as $customerDataKey => $customerDataValue) {
                        switch ($customerDataKey) {
                            case 'email':
                                $customer->setEmail($customerDataValue);
                                break;
                            case 'password':
                                $customer->setPassword($customerDataValue);
                                $isPasswordChanged = true;
                                break;
                            case 'lastname':
                                $customer->setLastname($customerDataValue);
                                break;
                            case 'firstname':
                                $customer->setFirstname($customerDataValue);
                                break;
                            case 'lastname_kana':
                                $customer->setLastnameKana($customerDataValue);
                                break;
                            case 'firstname_kana':
                                $customer->setFirstnameKana($customerDataValue);
                                break;
                            case 'gender':
                                $customer->setGender($customerDataValue);
                                break;
                            case 'dob':
                                $dob = date("Y-m-d", strtotime($customerDataValue));
                                $customer->setDob($dob);
                                break;
                            case 'occupation':
                                $customer->setOccupation($customerDataValue);
                                break;
                        }
                    }
                    $saved = $this->customerResource->save($customer);
                    if ($saved && $isPasswordChanged) {
                        $result['accessToken'] = $this->crmHelper->generateAccessToken($customer->getPasswordHash());
                    }
                }
                if ($customer->getDefaultBillingAddress()) {
                    $customerAddress = $this->addressFactory->create()->load($customer->getDefaultBillingAddress()->getId());
                    $customerAddressData = $this->mapCustomerAddressData($params, $customerAddress);
                    if (!empty($customerAddressData)) {
                        foreach ($customerAddressData as $customerAddressDataKey => $customerAddressDataValue) {
                            switch ($customerAddressDataKey) {
                                case 'lastname':
                                    $customerAddress->setLastname($customerAddressDataValue);
                                    break;
                                case 'firstname':
                                    $customerAddress->setFirstname($customerAddressDataValue);
                                    break;
                                case 'lastname_kana':
                                    $customerAddress->setLastnameKana($customerAddressDataValue);
                                    break;
                                case 'firstname_kana':
                                    $customerAddress->setFirstnameKana($customerAddressDataValue);
                                    break;
                                case 'street':
                                    $customerAddress->setStreet($customerAddressDataValue);
                                    break;
                                case 'region':
                                    $customerAddress->setRegion($customerAddressDataValue);
                                    break;
                                case 'region_id':
                                    $customerAddress->setRegionId($customerAddressDataValue);
                                    break;
                                case 'postcode':
                                    $customerAddress->setPostcode($customerAddressDataValue);
                                    break;
                                case 'telephone':
                                    $customerAddress->setTelephone($customerAddressDataValue);
                                    break;
                            }
                        }
                        $customerAddress->save();
                    }
                }

                if (!is_null($newsletterSubscriptionStatus) && $email) {
                    $this->updateNewsletter($newsletterSubscriptionStatus, $params[Param::MEMBER_ID], $storeId);
                }
            }

            if ($resultCode == Result::INVALID_PASSWORD_LENGTH) {
                $resultExplanation = Result::RESULT_CODES[$resultCode];
                $resultExplanation = str_replace('$1', $this->validation->getMinimumPasswordLength(), $resultExplanation);
                $resultExplanation = str_replace('$2', Param::PARAMS_LENGTH[Param::PASSWORD], $resultExplanation);
            } else {
                $resultExplanation = Result::RESULT_CODES[$resultCode];
            }

            $result = array_merge($result, [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ]);
        } catch (\Exception $e) {
            $this->crmHelper->logCritical($e->getMessage());
            $resultCode = Result::INTERNAL_ERROR;
            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $result = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ];
        }

        echo json_encode($result);
        exit();
    }

    private function mapCustomerData($data, $customer)
    {
        $gender = isset($data[Param::GENDER]) ? $data[Param::GENDER] : null;
        $data = array_filter($data);

        $mappedData = [
            'email' => isset($data[Param::EMAIL]) ? $data[Param::EMAIL] : '',
            'password' => isset($data[Param::PASSWORD]) ? $data[Param::PASSWORD] : '',
            'firstname' => isset($data[Param::FIRSTNAME]) ? $data[Param::FIRSTNAME] : '',
            'lastname' => isset($data[Param::LASTNAME]) ? $data[Param::LASTNAME] : '',
            'firstname_kana' => isset($data[Param::FIRSTNAME_KANA]) ? $data[Param::FIRSTNAME_KANA] : '',
            'lastname_kana' => isset($data[Param::LASTNAME_KANA]) ? $data[Param::LASTNAME_KANA] : '',
            'gender' =>  $this->crmHelper->getGender($gender),
            'dob' => isset($data[Param::DOB]) ? $data[Param::DOB] : ''
        ];

        return array_filter($mappedData);
    }

    private function mapCustomerAddressData($data, $customerAddress)
    {
        $regionId = "";
        $region = $data[Param::PREFECTURE] ?? "";
        if ($region) {
            $regionId = $this->crmHelper->getRegionIdByName($region, ($data[Param::COUNTRY_CODE] ?? 'JP'));
            if (!$regionId) {
                $regionId =  "";
            }
        }
        $mappedData = [
            'firstname' => isset($data[Param::FIRSTNAME]) ? $data[Param::FIRSTNAME] : '',
            'lastname' => isset($data[Param::LASTNAME]) ? $data[Param::LASTNAME] : '',
            'firstname_kana' => isset($data[Param::FIRSTNAME_KANA]) ? $data[Param::FIRSTNAME_KANA] : '',
            'lastname_kana' => isset($data[Param::LASTNAME_KANA]) ? $data[Param::LASTNAME_KANA] : '',
            'region' => $region,
            'region_id' => $regionId
        ];

        if (isset($data[Param::ADDRESS_1]) || isset($data[Param::ADDRESS_2])) {
            $mappedData = array_merge($mappedData, [
                'street' => array(
                    '0' => isset($data[Param::ADDRESS_1]) && !empty($data[Param::ADDRESS_1]) ? $data[Param::ADDRESS_1] : "　",
                    '1' => isset($data[Param::ADDRESS_2]) && !empty($data[Param::ADDRESS_2]) ? $data[Param::ADDRESS_2] : '',
                )
            ]);
        }

        $postCode = explode('-', $customerAddress->getPostcode());
        for ($i = 0; $i <= 1;) {
            $postCode[$i] = isset($data['postalCode' . ($i+1)]) && !empty($data['postalCode' . ($i+1)]) ?
                $data['postalCode' . ($i+1)] : (isset($postCode[$i]) && !empty($postCode[$i]) ? $postCode[$i] : '');
            $i++;
        }

        $telephone = explode('-', $customerAddress->getTelephone());
        for ($i = 0; $i <= 2;) {
            $telephone[$i] = isset($data['phone' . ($i+1)]) && !empty($data['phone' . ($i+1)]) ?
                $data['phone' . ($i+1)] : (isset($telephone[$i]) && !empty($telephone[$i]) ? $telephone[$i] : '');
            $i++;
        }
    
        $mappedData = array_merge($mappedData, [
            'postcode' => implode("-", $postCode),
            'telephone' => implode("-", $telephone)
        ]);

        return array_filter($mappedData);
    }

    public function updateNewsletter($status, $customerId, $storeId)
    {
        try {
            if ($status) {
                $this->subscriptionManager->subscribeCustomer($customerId, $storeId);
            } else {
                $this->subscriptionManager->unsubscribeCustomer($customerId, $storeId);
            }
        } catch (\Exception $e) {
            $resultCode = Result::INTERNAL_ERROR;
            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $result = [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ];
        }
    }
}
