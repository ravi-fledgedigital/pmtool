<?php

namespace Cpss\Crm\Model\Btoc;

use Magento\Customer\Model\AddressFactory;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Customer\Model\Session;
use Cpss\Crm\Helper\Customer;
use Cpss\Crm\Model\Btoc\Config\Result;
use Cpss\Crm\Model\Btoc\Config\Param;
use \Magento\Newsletter\Model\SubscriberFactory;
use Cpss\Crm\Model\CpssApiRequest;
use Cpss\Crm\Helper\MemberValidation;

class Register implements \Cpss\Crm\Api\Btoc\RegisterInterface
{
    protected $addressFactory;
    protected $indexerFactory;
    protected $customerSession;
    protected $crmHelper;
    protected $subscriberFactory;
    protected $cpssApiRequest;
    protected $validation;

    public function __construct(
        AddressFactory $addressFactory,
        IndexerFactory $indexerFactory,
        Session $customerSession,
        Customer $crmHelper,
        SubscriberFactory $subscriberFactory,
        CpssApiRequest $cpssApiRequest,
        MemberValidation $validation
    ) {
        $this->addressFactory = $addressFactory;
        $this->indexerFactory = $indexerFactory;
        $this->customerSession = $customerSession;
        $this->crmHelper = $crmHelper;
        $this->subscriberFactory = $subscriberFactory;
        $this->cpssApiRequest = $cpssApiRequest;
        $this->validation = $validation;
    }

    public function registerMember()
    {
        header('Content-Type: application/json');
        $response = [];
        try {
            $success = Result::SUCCESS;
            $data = $this->crmHelper->getParamsRequest();
            $this->crmHelper->logDebug("REGISTER", $data);
            $resultCode = $this->validation->validateParams(Param::REGISTER_MEMBER_PARAMS, $data);
            if ($resultCode === $success) {
                $resultCode = $this->crmHelper->validateEmailAvailable($data['email']);
            }

            if ($resultCode === $success) {
                $websiteId  = $this->crmHelper->getWebsiteId();
                $customer   = $this->crmHelper->getCustomerFactory();
                $customer->setWebsiteId($websiteId);

                // Register Customer
                $customer->setEmail($data['email']);
                $customer->setPassword($data['password']);
                $customer->setLastname($data['lastName']);
                $customer->setFirstname($data['firstName']);
                $lastNameKana = isset($data['lastNameKana']) && !empty($data['lastNameKana']) ? $data['lastNameKana'] : '　';
                $firstNameKana = isset($data['firstNameKana']) && !empty($data['firstNameKana']) ? $data['firstNameKana'] : '　';
                $customer->setLastnameKana($lastNameKana);
                $customer->setFirstnameKana($firstNameKana);
                if (isset($data['gender']) && $data['gender'] !== '') {
                    $gender = $this->crmHelper->getGender((int)$data['gender']);
                    $customer->setGender($gender);
                }

                if (isset($data['birthDay']) && !empty($data['birthDay'])) {
                    $customer->setDob(date("Y-m-d", strtotime($data['birthDay'])));
                }

                if (isset($data['occupation']) && !empty($data['occupation'])) {
                    $customer->setOccupation($data['occupation']);
                }
                // New Agreement
                $customer->setIsAgreed(1);
                $customer->save();

                // Register Customer Address (set as default Billing Address)
                if ($customer->getId()) {
                    $customAddress = $this->addressFactory->create();

                    $regionId = "";
                    $region = $data['prefecture'] ?? "";
                    if ($region) {
                        $regionId = $this->crmHelper->getRegionIdByName($region, $data['countryCode']);
                        if (!$regionId) {
                            $regionId =  "";
                        }
                    }

                    $postalCode = (isset($data['postalCode1'], $data['postalCode2']))
                        && $data['postalCode1'] && $data['postalCode2'] ?
                        $data['postalCode1'] . '-' . $data['postalCode2'] : '　';
                    $phone = (isset($data['phone1'], $data['phone2'], $data['phone3']))
                        && $data['phone1'] && $data['phone2'] && $data['phone3'] ?
                        $data['phone1'] . '-' . $data['phone2'] . '-' . $data['phone3'] : '　';
                    $address1 = isset($data['address1']) && !empty($data['address1']) ? $data['address1'] : "　";
                    $customAddress->setData(
                        [
                            'firstname' => $data['firstName'],
                            'lastname' => $data['lastName'],
                            'firstname_kana' => $firstNameKana,
                            'lastname_kana' => $lastNameKana,
                            'street' => array(
                                '0' => $address1, // this is mandatory
                                '1' => $data['address2'] ?? "" // this is optional
                            ),
                            'country_id' => isset($data['countryCode']) ? strtoupper($data['countryCode']) : 'JP',
                            'city' => "",
                            'region' => $region,
                            'postcode' => $postalCode,
                            'telephone' => $phone,
                        ]
                    );

                    if ($regionId) {
                        $customAddress->setRegionId($regionId);
                    }

                    $customAddress->setCustomerId($customer->getId())->setIsDefaultBilling('1');
                    $customAddress->save();
                }


                if ($customer->getId() && $customAddress->getId()) {
                    // Newsletter Subscription
                    if (isset($data['subscribeNewsLetter']) &&  (int)$data['subscribeNewsLetter'] == 1 && $customer->getId()) {
                        $subscription = $this->subscriberFactory->create();
                        $customerSubscriber = $subscription->loadByCustomerId($customer->getId());
                        $customerSubscriber->subscribe($customer->getEmail());
                        $customerSubscriber->setCustomerId($customer->getId());
                        $customerSubscriber->setStoreId(1);
                        if (isset($data['gender'])) {
                            $customerSubscriber->setGender($data['gender']);
                        }
                        $customerSubscriber->save();
                    }

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
                    if ($customAddress->getId()) {
                        $customAddress->delete();
                    }
                    $resultCode = Result::INTERNAL_ERROR;
                }
            }

            if ($resultCode == Result::INVALID_PASSWORD_LENGTH) {
                $resultExplanation = Result::RESULT_CODES[$resultCode];
                $resultExplanation = str_replace('$1', $this->validation->getMinimumPasswordLength(), $resultExplanation);
                $resultExplanation = str_replace('$2', Param::PARAMS_LENGTH[Param::PASSWORD], $resultExplanation);
            } else {
                $resultExplanation = Result::RESULT_CODES[$resultCode];
            }

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
            if (isset($customAddress) && $customAddress->getId()) {
                $customAddress->delete();
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
}
