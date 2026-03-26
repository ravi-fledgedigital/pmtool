<?php

namespace OnitsukaTigerCpss\Crm\Model\Btoc;

use Cpss\Crm\Model\Btoc\Config\Result;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer as ModelCustomer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\App\Emulation;
use OnitsukaTiger\Newsletter\Helper\Data as NewsletterHelper;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelper;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;
use OnitsukaTigerCpss\Crm\Model\Btoc\Config\Param;

/**
 * update api /V1/updateMemberInfo
 */
class Update extends \Cpss\Crm\Model\Btoc\Update
{
    protected $addressFactory;
    protected $crmHelper;
    protected $validation;
    protected $customerResource;
    protected $subscriptionManager;
    protected CustomerFactory $customerFactory;
    protected CustomerCollectionFactory $customerCollectionFactory;
    private CountryFactory $countryFactory;
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var Subscriber
     */
    protected Subscriber $subscriber;
    /**
     * @var EmailNotificationInterface
     */
    protected EmailNotificationInterface $emailNotification;
    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;
    /**
     * @var NewsletterHelper
     */
    private NewsletterHelper $newsletterHelper;
    private bool $isEmailChanged = false;
    private bool $isPasswordChanged = false;

    /**
     * @param AddressFactory $addressFactory
     * @param CustomerHelper $crmHelper
     * @param MemberValidation $validation
     * @param Customer $customerResource
     * @param SubscriptionManagerInterface $subscriptionManager
     * @param Emulation $emulation
     * @param Subscriber $subscriber
     * @param CustomerFactory $customerFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param CountryFactory $countryFactory
     * @param EmailNotificationInterface $emailNotification
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        AddressFactory               $addressFactory,
        CustomerHelper               $crmHelper,
        MemberValidation             $validation,
        Customer                     $customerResource,
        SubscriptionManagerInterface $subscriptionManager,
        Emulation                    $emulation,
        Subscriber                   $subscriber,
        CustomerFactory              $customerFactory,
        CustomerCollectionFactory    $customerCollectionFactory,
        CountryFactory               $countryFactory,
        EmailNotificationInterface   $emailNotification,
        CustomerRepositoryInterface  $customerRepository,
        NewsletterHelper             $newsletterHelper,
    ) {
        $this->crmHelper = $crmHelper;
        $this->emulation = $emulation;
        $this->subscriber = $subscriber;
        $this->subscriptionManager = $subscriptionManager;
        $this->validation = $validation;
        $this->customerFactory = $customerFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->countryFactory = $countryFactory;
        $this->emailNotification = $emailNotification;
        $this->customerRepository = $customerRepository;
        $this->newsletterHelper = $newsletterHelper;
        parent::__construct($addressFactory, $crmHelper, $validation, $customerResource, $subscriptionManager);
    }

    public function updateMember()
    {
        header('Content-Type: application/json');
        $result = [];
        try {
            $success = Result::SUCCESS;
            $params = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("UPDATE", $params);

            $resultCode = $this->validation->validateExistSite($params);
            if ($resultCode !== $success) {
                $resultExplanation = $this->getStr($resultCode);
                $result = array_merge($result, [
                    "resultCode" => $resultCode,
                    "resultExplanation" => $resultExplanation
                ]);
                echo json_encode($result);
                exit();
            }
            if (!empty($params[Param::SITE_ID])) {
                $storeId = $this->crmHelper->getStoreDefaultId($params[Param::SITE_ID]);
                $this->emulation->startEnvironmentEmulation($storeId, 'frontend', true);
            }
            $rules = Param::REQUEST_UPDATE_MEMBER_PARAMS;
            // lastname for KR site
            list($params, $rules) = $this->lastnameForKRSite($params, $rules);

            $resultCode = $this->validation->validateData($rules, $params);
            $params = $this->trimParameters($params);

            if ($resultCode === $success) {
                list($websiteId, $customer, $params, $resultCode) = $this->validEmailByWebsite($params);
            }

            if (isset($params[Param::SITE_ID]) && $params[Param::SITE_ID] == MemberValidation::COUNTRY_SITE_KR) {
                $params[Param::LASTNAME] = '&nbsp';
            }
            if ($resultCode === $success) {
                $customerData = $this->mapCustomerData($params, $customer);
                $this->isPasswordChanged = false;
                $this->isEmailChanged = false;
                $email = isset($customerData['email']) ? $customerData['email'] : $customer->getEmail();
                $currentCustomer = $this->customerRepository->get($customer->getEmail(), $params[Param::SITE_ID]);
                if (!empty($customerData)) {
                    $customer->setWebsiteId($websiteId);
                    foreach ($customerData as $customerDataKey => $customerDataValue) {
                        switch ($customerDataKey) {
                            case 'email':
                                $customer->setEmail($customerDataValue);
                                $this->isEmailChanged = true;
                                break;
                            case 'password':
                                $customer->setPassword($customerDataValue);
                                $this->isPasswordChanged = true;
                                break;
                            case 'lastname':
                                $customer->setLastname($customerDataValue);
                                break;
                            case 'firstname':
                                $customer->setFirstname($customerDataValue);
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
                    $result = $this->sendMailChanged($saved, $customer, $result, $currentCustomer, $params[Param::SITE_ID]);
                }
                $this->saveBillingAddress($customer, $params);
                $this->updateNewsletterByCustomer($params, $customer, $storeId);
            }
            $this->emulation->stopEnvironmentEmulation();
            $resultExplanation = $this->getStr($resultCode);

            $result = array_merge($result, [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ]);
        } catch (\Exception $e) {
            $this->crmHelper->logCritical($e->getMessage());
            $this->crmHelper->logCritical($e->getFile());
            $this->crmHelper->logCritical($e->getLine());
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
        $gender = isset($data[Param::GENDER]) ? $data[Param::GENDER] : '';
        $mappedData = [
            'email' => !empty($data[Param::EMAIL]) ? $data[Param::EMAIL] : '',
            'password' => isset($data[Param::PASSWORD]) ? $data[Param::PASSWORD] : '',
            'gender' => $this->crmHelper->getGender($gender),
            'dob' => !empty($data[Param::DOB]) ? $data[Param::DOB] : '',
            'occupation' => !empty($data[Param::OCCUPATION]) ? $data[Param::OCCUPATION] : ''
        ];
        $mappedData= array_merge($mappedData, $this->formatFirstName($data, $mappedData));
        $mappedData= array_merge($mappedData, $this->formatLastname($data, $mappedData));
        return array_filter($mappedData, function ($value) {
            return strlen($value) != 0;
        });
    }

    private function mapCustomerAddressData($data, $customerAddress)
    {
        $regionId = null;
        $region = $this->formatPrefecture($data);
        $countryCode = !empty($data[Param::COUNTRY_CODE]) ? $data[Param::COUNTRY_CODE] :
            MemberValidation::WEBSITE_COUNTRY_CODE[$data[Param::SITE_ID]];
        if ($region) {
            $regionId = $this->crmHelper->getRegionIdByName($region, $countryCode);
        }
        $mappedData = [
            'firstname' => isset($data[Param::FIRSTNAME]) && !empty($data[Param::FIRSTNAME]) ? $data[Param::FIRSTNAME] : $customerAddress->getData('firstname'),
            'lastname' => isset($data[Param::LASTNAME])  && !empty($data[Param::LASTNAME]) ? $data[Param::LASTNAME] : $customerAddress->getData('lastname'),
        ];
        list($data, $mappedData) = $this->formatRegions($region, $data, $regionId, $mappedData);

        if (!empty($data[Param::PHONE_1])) {
            $mappedData['telephone'] = $this->formatTelephone($customerAddress, $data);
        }
        if (!empty($data[Param::POSTAL_CODE_1])) {
            $mappedData['postcode'] = $data[Param::POSTAL_CODE_1];
        }
        $mappedData = $this->formatBillingAddress($customerAddress, $data, $mappedData);
        return $mappedData;
    }

    private function formatTelephone($customer, $params)
    {
        if (empty($params[Param::PHONE_1])) {
            return '';
        }
        $telephonePrefix = $this->crmHelper->getTelephoneCountryCode($customer);
        return $telephonePrefix . $params[Param::PHONE_1];
    }

    /**
     * @param mixed $resultCode
     * @return array|string|string[]
     */
    public function getStr(mixed $resultCode): string|array
    {
        if ($resultCode == Result::INVALID_PASSWORD_LENGTH) {
            $resultExplanation = Result::RESULT_CODES[$resultCode];
            $resultExplanation = str_replace('$1', $this->validation->getMinimumPasswordLength(), $resultExplanation);
            $resultExplanation = str_replace('$2', Param::PARAMS_LENGTH[Param::PASSWORD], $resultExplanation);
        } else {
            $resultExplanation = Result::RESULT_CODES[$resultCode];
        }
        return $resultExplanation;
    }

    /**
     * Save or Create billing address
     * @param ModelCustomer $customer
     * @param array $params
     * @param $firstNameKana
     * @param $lastNameKana
     * @return mixed|null
     */
    public function saveBillingAddress(ModelCustomer $customer, array $params, $firstNameKana = null, $lastNameKana = null)
    {
        $customerAddress = $customer->getDefaultBillingAddress();
        if (!$customerAddress) {
            if (empty($params[Param::PHONE_1]) || empty($params[Param::POSTAL_CODE_1])) {
                return null;
            }
            return $this->registerCustomerAddress($customer, $params, $firstNameKana, $lastNameKana);
        }
        return $this->updateBillingAddress($params, $customerAddress);
    }

    /**
     * @param $customerId
     * @param $email
     * @return bool
     */
    public function isEmailAlreadyExists($customer, $email, $websiteId): bool
    {
        // Check if the email has been changed
        if ($customer->getEmail() !== $email) {
            // Check if the email already exists for another customer
            $existingCustomer = $this->customerCollectionFactory->create()
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('entity_id', ['neq' => $customer->getId()])
                ->addAttributeToFilter('website_id', $websiteId)
                ->getFirstItem();

            if ($existingCustomer->getId()) {
                return true; // Email already exists for another customer
            }
        }
        return false; // Email is unique or not changed
    }

    /**
     * @param ModelCustomer $customer
     * @param array $data
     * @param mixed $firstNameKana
     * @param mixed $lastNameKana
     * @return mixed
     */
    public function registerCustomerAddress(ModelCustomer $customer, array $data, mixed $firstNameKana, mixed $lastNameKana)
    {
        $customAddress = $this->addressFactory->create();
        $regionId = "";
        $region = !empty($data[Param::PREFECTURE]) ? $data[Param::PREFECTURE] : "";
        $countryCode = !empty($data[Param::COUNTRY_CODE]) ? $data[Param::COUNTRY_CODE]
            : MemberValidation::WEBSITE_COUNTRY_CODE[$data[Param::SITE_ID]];
        if ($region) {
            $regionId = $this->crmHelper->getRegionIdByName($region, $countryCode);
        }

        $postalCode = isset($data[Param::POSTAL_CODE_1]) ? $data[Param::POSTAL_CODE_1] : '';
        $phone = $this->formatTelephone($customer, $data);
        $address1 = isset($data[Param::ADDRESS_1]) && !empty($data[Param::ADDRESS_1]) ? $data[Param::ADDRESS_1] : "　";
        $firstname = !empty($data[Param::FIRSTNAME]) ? $data[Param::FIRSTNAME] : $customer->getData('firstname');
        $lastname = !empty($data[Param::FIRSTNAME]) ? $data[Param::FIRSTNAME] : $customer->getData('lastname');
        $customAddress->setData(
            [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'firstname_kana' => $firstNameKana,
                'lastname_kana' => $lastNameKana,
                'street' => [
                    '0' => $address1, // this is mandatory
                    '1' => $data['address2'] ?? "" // this is optional
                ],
                'country_id' => $countryCode,
                'city' => $this->getCountryname($countryCode),
                'region' => $region,
                'postcode' => $postalCode,
                'telephone' => $phone,
                'region_id' => $regionId,
            ]
        );

        $customAddress->setCustomerId($customer->getId())->setIsDefaultBilling(1);
        $customAddress->save();
        return $customAddress;
    }

    /**
     * @param $countryCode
     * @return string
     */
    public function getCountryname($countryCode)
    {
        if (empty($countryCode)) {
            return '';
        }
        $country = $this->countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     * @param $customerAddress
     * @param $data
     * @return mixed
     */
    public function extractPostCode($customerAddress, $data): mixed
    {
        $postalCode = "";
        $postalCode1 = !empty($data[Param::POSTAL_CODE_1]) ? $data[Param::POSTAL_CODE_1] : '';
        $postalCode2 = !empty($data[Param::POSTAL_CODE_2]) ? $data[Param::POSTAL_CODE_2] : '';
        if (isset($postalCode1) && isset($postalCode2)) {
            if (!empty($postalCode1) && !empty($postalCode2)) {
                $postalCode = $postalCode1 . "-" . $postalCode2;
            } elseif (!empty($postalCode1)) {
                $postalCode = $postalCode1;
            } elseif (!empty($postalCode2)) {
                $postalCode = $postalCode2;
            }
        }
        return $postalCode;
    }

    /**
     * @param $customerAddress
     * @param $data
     * @return string
     */
    public function extractTelephone($customerAddress, $data): string
    {
        $telephone = explode('-', $customerAddress->getTelephone());
        for ($i = 0; $i <= 2;) {
            $telephone[$i] = isset($data['phone' . ($i + 1)]) && !empty($data['phone' . ($i + 1)]) ?
                $data['phone' . ($i + 1)] : (isset($telephone[$i]) && !empty($telephone[$i]) ? $telephone[$i] : '');
            $i++;
        }
        return implode("", $telephone);
    }

    /**
     * @param $paramKeys
     * @param $params
     * @return mixed
     */
    private function trimParameters($params)
    {
        foreach ($params as $key => &$value) {
            if (!in_array($key, [Param::PASSWORD])) {
                $params[$key] = trim($value);
            }
        }
        return $params;
    }

    /**
     * @param mixed $params
     * @return array
     */
    public function validEmailByWebsite(mixed $params): array
    {
        $websiteId = $params[Param::SITE_ID];

        $customer = $this->crmHelper->getCustomerFactory();
        $customer = $customer->load($params[Param::MEMBER_ID]);
        $isEmailAlreadyExists = false;
        if (!empty($params[Param::EMAIL])) {
            $isEmailAlreadyExists = $this->isEmailAlreadyExists($customer, $params[Param::EMAIL], $websiteId);
        }
        if ($customer->getWebsiteId() !== $websiteId) {
            $resultCode = Result::ACCESS_DENIED;
        } elseif ($isEmailAlreadyExists) {
            $resultCode = Result::EMAIL_EXISTS;
        } elseif ($customer->getId()) {
            $resultCode = $this->crmHelper->auth($customer->getPasswordHash(), $params[Param::ACCESS_TOKEN]);
        } else {
            $resultCode = Result::ACCESS_DENIED;
        }
        return [$websiteId, $customer, $params, $resultCode];
    }

    /**
     * Get email notification
     *
     * @return EmailNotificationInterface
     * @deprecated 100.1.0
     */
    private function getEmailNotification(): EmailNotificationInterface
    {
        if (!($this->emailNotification instanceof EmailNotificationInterface)) {
            return ObjectManager::getInstance()->get(
                EmailNotificationInterface::class
            );
        } else {
            return $this->emailNotification;
        }
    }

    /**
     * @param array $newsletterSubscriptionStatus
     * @param ModelCustomer $customer
     * @param int $storeId
     * @return void
     */
    private function updateNewsletterByCustomer($params, ModelCustomer $customer, int $storeId)
    {
        $newsletterSubscriptionStatus = isset($params['subscribeNewsLetter']) ? $params['subscribeNewsLetter'] : null;
        try {
            $checkSubscriber = $this->subscriber->loadByCustomer($customer->getId(), $params[Param::SITE_ID]);
            if (empty($checkSubscriber->getId()) && !$this->isEmailChanged) {
                return;
            }
            if ($newsletterSubscriptionStatus && !empty($checkSubscriber->getId()) && !$this->isEmailChanged) {
                $checkSubscriber->setStatus($newsletterSubscriptionStatus);
                $checkSubscriber->save();
            }

            if ($checkSubscriber->getId() && $this->isEmailChanged) {
                $subscriptionStatus = $newsletterSubscriptionStatus ? $newsletterSubscriptionStatus : $checkSubscriber->getStatus();
                $checkSubscriber = $this->subscriptionManager->subscribeCustomer($customer->getId(), $storeId);
                $checkSubscriber->setData('customer_id', $customer->getId());
                $checkSubscriber->setStatus($subscriptionStatus);
                $checkSubscriber->setData('gender', $customer->getData('gender'));
                $checkSubscriber->setData('dob', $customer->getData('dob'));
                $checkSubscriber->save();
                return;
            }

            if (empty($checkSubscriber->getId()) && $this->isEmailChanged && $newsletterSubscriptionStatus) {
                $checkSubscriber = $this->subscriptionManager->subscribeCustomer($customer->getId(), $storeId);
                $checkSubscriber->setData('gender', $customer->getData('gender'));
                $checkSubscriber->setData('dob', $customer->getData('dob'));
                $checkSubscriber->setStatus($newsletterSubscriptionStatus);
                $checkSubscriber->save();
//                if($newsletterSubscriptionStatus == Subscriber::STATUS_SUBSCRIBED){
//                    $this->newsletterHelper->sendDiscountCode($params[Param::EMAIL]);
//                }
                return;
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

    /**
     * @param array $params
     * @param $customerAddress
     * @return mixed
     */
    public function updateBillingAddress(array $params, $customerAddress)
    {
        $customerAddressData = $this->mapCustomerAddressData($params, $customerAddress);
        if (empty($customerAddressData)) {
            return $customerAddress;
        }
        foreach ($customerAddressData as $customerAddressDataKey => $customerAddressDataValue) {
            switch ($customerAddressDataKey) {
                case 'lastname':
                    $customerAddress->setLastname($customerAddressDataValue);
                    break;
                case 'firstname':
                    $customerAddress->setFirstname($customerAddressDataValue);
                    break;
                case 'street':
                    $customerAddress->setStreet($customerAddressDataValue);
                    break;
                case 'state':
                    $customerAddress->setState($customerAddressDataValue);
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
        return $customerAddress;
    }

    /**
     * @param array $params
     * @param array $rules
     * @return array
     */
    public function lastnameForKRSite(array $params, array $rules): array
    {
        if (isset($params[Param::SITE_ID]) && $params[Param::SITE_ID] != MemberValidation::COUNTRY_SITE_KR) {
            return [$params, $rules];
        }
        if (!isset($params[Param::FIRSTNAME])) {
            return [$params, $rules];
        }
        $params[Param::LASTNAME] = $params[Param::FIRSTNAME];
        $rules = array_merge($rules, Param::REQUEST_UPDATE_MEMBER_KR_PARAMS);
        return [$params, $rules];
    }

    /**
     * @param $customerAddress
     * @param $data
     * @param array $mappedData
     * @return [][]|array
     */
    public function formatBillingAddress($customerAddress, $data, array $mappedData): array
    {
        $address = $customerAddress->getStreet();
        $address1 = !empty($address[0]) ? $address[0] : "";
        $address2 = !empty($address[1]) ? $address[1] : "";
        $paramAddress1 = isset($data[Param::ADDRESS_1]) ? $data[Param::ADDRESS_1] : "";
        $paramAddress2 = isset($data[Param::ADDRESS_2]) ? $data[Param::ADDRESS_2] : "";

        if (empty($address1) && empty($address2) && empty($paramAddress1) && empty($paramAddress2)) {
            return $mappedData;
        }
        if (!isset($data[Param::ADDRESS_1]) &&  !isset($data[Param::ADDRESS_2])) {
            return $mappedData;
        }
        if (empty($paramAddress1) && empty($paramAddress2) && !empty($address1)) {
            $paramAddress1 = $address1;
        }
        if (!isset($data[Param::ADDRESS_2])  && !empty($address2)) {
            $paramAddress2 = $address2;
        }
        if (!isset($data[Param::ADDRESS_1])  && !empty($address1)) {
            $paramAddress1 = $address1;
        }
        return array_merge($mappedData, [
            'street' => [
                '0' => $paramAddress1,
                '1' => $paramAddress2,
            ]
        ]);
    }

    /**
     * @param $data
     * @param $customer
     * @return array
     */
    private function formatFirstName($data, array $mappedData): array
    {
        if (!isset($data[Param::FIRSTNAME]) || (isset($data[Param::FIRSTNAME]) && empty($data[Param::FIRSTNAME]))) {
            return $mappedData;
        }
        return array_merge($mappedData, [
            'firstname' => $data[Param::FIRSTNAME]
        ]);
    }

    private function formatLastname($data, array $mappedData)
    {
        if (!isset($data[Param::LASTNAME]) || isset($data[Param::LASTNAME]) && empty($data[Param::LASTNAME])) {
            return $mappedData;
        }
        return array_merge($mappedData, [
            'lastname' => $data[Param::LASTNAME]
        ]);
    }

    /**
     * @param $customerSaved
     * @param mixed $customer
     * @param array $result
     * @param CustomerInterface $currentCustomer
     * @param $websiteId1
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sendMailChanged($customerSaved, mixed $customer, array $result, CustomerInterface $currentCustomer, $websiteId1): array
    {
        if ($customerSaved && $this->isEmailChanged && $this->isPasswordChanged) {
            $newCustomer = $this->customerRepository->get($customer->getEmail(), $websiteId1);
            $result['accessToken'] = $this->crmHelper->generateAccessToken($customer->getPasswordHash());
            if ($websiteId1 == MemberValidation::COUNTRY_SITE_KR) {
                $newCustomer->setData('lastname', '');
            }
            $this->getEmailNotification()->credentialsChanged(
                $newCustomer,
                $currentCustomer->getEmail(),
                true
            );
            if ($websiteId1 == MemberValidation::COUNTRY_SITE_KR) {
                $newCustomer->setData('lastname', '&nbsp');
            }
            return $result;
        }
        if ($customerSaved && $this->isEmailChanged && isset($currentCustomer)) {
            $newCustomer = $this->customerRepository->get($customer->getEmail(), $websiteId1);
            if ($websiteId1 == MemberValidation::COUNTRY_SITE_KR) {
                $newCustomer->setData('lastname', '');
            }
            $this->getEmailNotification()->credentialsChanged(
                $newCustomer,
                $currentCustomer->getEmail(),
                false
            );
            if ($websiteId1 == MemberValidation::COUNTRY_SITE_KR) {
                $newCustomer->setData('lastname', '&nbsp');
            }
            return [];
        }
        if ($customerSaved && $this->isPasswordChanged) {
            if ($websiteId1 == MemberValidation::COUNTRY_SITE_KR) {
                $currentCustomer->setData('lastname', '');
            }
            $result['accessToken'] = $this->crmHelper->generateAccessToken($customer->getPasswordHash());
            $this->getEmailNotification()->credentialsChanged(
                $currentCustomer,
                $currentCustomer->getEmail(),
                true
            );
            if ($websiteId1 == MemberValidation::COUNTRY_SITE_KR) {
                $currentCustomer->setData('lastname', '&nbsp');
            }
            return $result;
        }

        return $result;
    }

    /**
     * @param $data
     * @return null
     */
    public function formatPrefecture($data)
    {
        return !empty($data[Param::PREFECTURE]) ? $data[Param::PREFECTURE] : null;
    }
    /**
     * @param $region
     * @param $data
     * @param string|null $regionId
     * @return array
     */
    public function formatRegions($region, $data, ?string $regionId, $mappedData): array
    {
        $regionsArray = [];
        if ($region) {
            $regionsArray['region'] = $region;
            $regionsArray['state'] = $data[Param::PREFECTURE];
        }
        if ($regionId) {
            $regionsArray['region_id'] = $regionId;
        }

        $mappedData = array_merge($mappedData, $regionsArray);
        return [$data, $mappedData];
    }
}
