<?php

namespace OnitsukaTigerCpss\Crm\Model\Btoc;

use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Model\CpssApiRequest;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\CountryFactory;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\App\Emulation;
use OnitsukaTiger\Newsletter\Helper\Data as NewsletterHelper;
use OnitsukaTigerCpss\Crm\Helper\Data as CustomerHelper;
use OnitsukaTigerCpss\Crm\Helper\MemberValidation;
use OnitsukaTigerCpss\Crm\Model\Btoc\Config\Param;

class Register extends \Cpss\Crm\Model\Btoc\Register
{
    protected $addressFactory;
    protected $indexerFactory;
    protected $customerSession;
    protected $crmHelper;
    protected $subscriberFactory;
    protected $cpssApiRequest;
    protected $validation;
    private NewsletterHelper $newsletterHelper;
    private Emulation $emulation;
    private CountryFactory $countryFactory;

    public function __construct(
        AddressFactory    $addressFactory,
        IndexerFactory    $indexerFactory,
        Session           $customerSession,
        CustomerHelper    $crmHelper,
        SubscriberFactory $subscriberFactory,
        CpssApiRequest    $cpssApiRequest,
        MemberValidation  $validation,
        NewsletterHelper  $newsletterHelper,
        Emulation         $emulation,
        CountryFactory    $countryFactory
    ) {
        $this->addressFactory = $addressFactory;
        $this->indexerFactory = $indexerFactory;
        $this->customerSession = $customerSession;
        $this->crmHelper = $crmHelper;
        $this->subscriberFactory = $subscriberFactory;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->validation = $validation;
        $this->newsletterHelper = $newsletterHelper;
        $this->emulation = $emulation;
        $this->countryFactory = $countryFactory;
        parent::__construct(
            $addressFactory,
            $indexerFactory,
            $customerSession,
            $crmHelper,
            $subscriberFactory,
            $cpssApiRequest,
            $validation
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function registerMember()
    {
        header('Content-Type: application/json');
        $response = [];
        try {
            $success = Result::SUCCESS;
            $data = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("REGISTER", $data);
            // lastname for KR site
            $rules = Param::REQUEST_REGISTER_MEMBER_PARAMS;
            list($data, $rules) = $this->lastnameForKRSite($data, $rules);
            $resultCode = $this->validation->validateData($rules, $data);
            if ($resultCode != $success) {
                $resultExplanation = $this->getStr($resultCode);
                $response = array_merge($response, [
                    "resultCode" => $resultCode,
                    "resultExplanation" => $resultExplanation
                ]);
                echo json_encode($response);
                exit();
            }
            $resultCode = $this->crmHelper->validateEmailAvailable($data['email'], $data[Param::SITE_ID]);

            $storeId = $this->crmHelper->getStoreDefaultId($data[Param::SITE_ID]);
            $this->emulation->startEnvironmentEmulation($storeId, 'frontend', true);

            // lastname for KR site
            if (isset($data[Param::SITE_ID]) && $data[Param::SITE_ID] == MemberValidation::COUNTRY_SITE_KR) {
                $data[Param::LASTNAME] = '&nbsp';
            }

            list($customer, $response, $resultCode) =
                $this->processRegister($resultCode, $success, $data, $response);
            $resultExplanation = $this->getStr($resultCode);

            $this->emulation->stopEnvironmentEmulation();
            $response = array_merge($response, [
                "resultCode" => $resultCode,
                "resultExplanation" => $resultExplanation
            ]);
        } catch (\Exception $e) {
            $this->crmHelper->logCritical(__("An error occured while registering member information."));
            $this->crmHelper->logCritical($e->getMessage());
            if (isset($customer) && $customer->getId()) {
                $customer->delete();
            }
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
     * @param int $resultCode
     * @param mixed $success
     * @param array $data
     * @param array $response
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Throwable
     */
    public function processRegister(int $resultCode, mixed $success, array $data, array $response): array
    {
        $customer = $customAddress = null;
        if ($resultCode === $success) {
            $websiteId = $data[Param::SITE_ID];
            $customer = $this->crmHelper->getCustomerFactory();
            $customer->setWebsiteId($websiteId);
            $storeId = $this->crmHelper->getStoreDefaultId($websiteId);

            // Register Customer
            $customer->setEmail($data['email']);
            $customer->setPassword($data['password']);

            $customer->setLastname($data['lastName']);
            $customer->setFirstname($data['firstName']);
            //Register Customer For KR
            $lastNameKana = isset($data['lastNameKana']) && !empty($data['lastNameKana']) ? $data['lastNameKana'] : '　';
            $firstNameKana = isset($data['firstNameKana']) &&
            !empty($data['firstNameKana']) ? $data['firstNameKana'] : '　';
            $customer->setLastnameKana($lastNameKana);
            $customer->setFirstnameKana($firstNameKana);
            if (isset($data['gender']) && $data['gender'] !== '') {
                $gender = $this->crmHelper->getGender((int)$data['gender']);
                $customer->setGender($gender);
            }

            if (isset($data['birthDay']) && !empty($data['birthDay'])) {
                $customer->setDob(date("Y-m-d", strtotime($data['birthDay'])));
            }

            if (!empty($data['occupation'])) {
                $customer->setOccupation($data['occupation']);
            }
            // New Agreement
            $customer = $this->newAgreement($customer);

            // Store Id
            $storeId = $this->crmHelper->getStoreDefaultId($customer->getWebsiteId());
            $store = $this->crmHelper->getStore($storeId);
            // Update 'created_in' value with actual store name
            $storeName = $store->getName();
            $customer->setCreatedIn($storeName);

            $customer->setStoreId($storeId);
            $customer->save();
            if ($customer->getId()) {
                // Newsletter Subscription
                $this->newsletterSubscription($data, $customer);

                //reindex customer grid index
                $indexer = $this->indexerFactory->create();
                $indexer->load('customer_grid');
                $indexer->reindexAll();

                // Generate Access token
                $accessToken = $this->crmHelper->generateAccessToken($customer->getPasswordHash());

                // Create customer session
                $customerSession = $this->customerSession;
                $customerSession->setCustomerAsLoggedIn($customer);

                // Send Email
                $customer->sendNewAccountEmail();

                // Register to CPSS
                $this->cpssApiRequest->addMember($this->crmHelper->getCpssMembeIdPrefix() . $customer->getId());

                $response = [
                    'memberId' => $customer->getId(), // FOR REVISION
                    'accessToken' => $accessToken
                ];
            } else {
                $this->crmHelper->logCritical(__("An error occured while registering member information."));
                if ($customer->getId()) {
                    $customer->delete();
                }
                $resultCode = Result::INTERNAL_ERROR;
            }
        }
        return [$customer, $response, $resultCode];
    }
    /**
     * @param mixed $data
     * @param \Magento\Customer\Model\Customer $customer
     * @return void
     * @throws \Exception
     */
    public function newsletterSubscription(mixed $data, \Magento\Customer\Model\Customer $customer): void
    {
        $subscription = $this->subscriberFactory->create();
        $customerSubscriberStatus = !empty($data['subscribeNewsLetter'])
            ? $data['subscribeNewsLetter'] : null;
        $customerSubscriber = $subscription->loadBySubscriberEmail($customer->getEmail(), $data[Param::SITE_ID]);

        if ($customerSubscriber->getId()) {
            if ($customerSubscriberStatus) {
                $customerSubscriber->setStatus($customerSubscriberStatus);
                $customerSubscriber->setData('customer_id', $customer->getId());
                $customerSubscriber->setData('gender', $customer->getData('gender'));
                $customerSubscriber->setData('dob', $customer->getData('dob'));
                $customerSubscriber->save();
            }
            return;
        } else {
            if (!$customerSubscriberStatus) {
                return;
            }
            $subscription->subscribeCustomerById($customer->getId());
            if ($customerSubscriberStatus == Subscriber::STATUS_SUBSCRIBED) {
                $this->newsletterHelper->sendDiscountCode($customer->getEmail());
                $subscription->setData('gender', $customer->getData('gender'));
                $subscription->setData('dob', $customer->getData('dob'));
                $subscription->save();
                return;
            } else {
                $subscription->setStatus($customerSubscriberStatus);
                $subscription->setData('gender', $customer->getData('gender'));
                $subscription->setData('dob', $customer->getData('dob'));
                $subscription->save();
                return;
            }
        }
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return \Magento\Customer\Model\Customer
     * @throws \Exception
     */
    public function newAgreement(\Magento\Customer\Model\Customer $customer)
    {
        $customer->setIsAgreed(1);
        $customer->save();
        return $customer;
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
     * @param array $params
     * @param array $rules
     * @return array
     */
    public function lastnameForKRSite(array $params, array $rules): array
    {
        if (isset($params[Param::SITE_ID]) && $params[Param::SITE_ID] != MemberValidation::COUNTRY_SITE_KR) {
            return [$params, $rules];
        }
        if (!isset($params[Param::LASTNAME]) || !isset($params[Param::FIRSTNAME])) {
            return [$params, $rules];
        }
        $params[Param::LASTNAME] = $params[Param::FIRSTNAME];
        $rules = array_merge($rules, Param::REQUEST_UPDATE_MEMBER_KR_PARAMS);
        return [$params, $rules];
    }
}
