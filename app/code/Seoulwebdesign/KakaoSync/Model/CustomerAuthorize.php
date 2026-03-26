<?php
/** phpcs:ignoreFile */

namespace Seoulwebdesign\KakaoSync\Model;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\UserLockedException;
use Seoulwebdesign\KakaoSync\Api\AccessTokenRepositoryInterface;
use Seoulwebdesign\KakaoSync\Service\Kakao;

class CustomerAuthorize
{

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var Kakao
     */
    protected $kakao;
    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerInterfaceFactory;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Seoulwebdesign\KakaoSync\Helper\ConfigHelper
     */
    protected $configHelper;
    /**
     * @var AccessTokenRepositoryInterface
     */
    protected $accessTokenRepository;
    /**
     * @var \Seoulwebdesign\KakaoSync\Helper\Logger
     */
    protected $logger;
    /**
     * @var CustomerExtractor
     */
    protected $customerExtractor;
    /**
     * @var AccountManagementInterface
     */
    protected $accountManagement;
    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var AuthenticationInterface
     */
    protected $authentication;
    private Session $customerSession;
    private \Magento\Framework\Message\ManagerInterface $messageManager;
    private ResultFactory $resultFactory;
    private \Magento\Framework\App\Http\Context $httpContext;

    /**
     * @param Kakao $kakao
     * @param CustomerFactory $customerFactory
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     * @param CustomerExtractor $customerExtractor
     * @param AccountManagementInterface $accountManagement
     * @param ManagerInterface $eventManager
     * @param AuthenticationInterface $authentication
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Kakao $kakao,
        CustomerFactory $customerFactory,
        CustomerInterfaceFactory $customerInterfaceFactory,
        CustomerRepositoryInterface $customerRepository,
        AccessTokenRepositoryInterface $accessTokenRepository,
        CustomerExtractor $customerExtractor,
        AccountManagementInterface $accountManagement,
        ManagerInterface $eventManager,
        Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ResultFactory $resultFactory,
        \Magento\Framework\App\Http\Context $httpContext,
        AuthenticationInterface $authentication
    ) {
        $this->kakao = $kakao;
        $this->customerFactory = $customerFactory;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->customerRepository = $customerRepository;
        $this->configHelper = $kakao->getConfigHelper();
        $this->accessTokenRepository  = $accessTokenRepository;
        $this->logger = $this->configHelper->getLogger();
        $this->customerExtractor  = $customerExtractor;
        $this->accountManagement  = $accountManagement;
        $this->eventManager  = $eventManager;
        $this->authentication  = $authentication;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
        $this->httpContext = $httpContext;
    }

    /**
     * Find customer by Id
     *
     * @param int $cusomerId
     * @return CustomerInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCustomer($cusomerId)
    {
        return $this->customerRepository->getById(
            $cusomerId
        );
    }

    /**
     * Find customer by email
     *
     * @param string $email
     * @return CustomerInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCustomerByEmail($email)
    {
        return $this->customerRepository->get(
            $email,
            $this->kakao->getConfigHelper()->getWebsiteId()
        );
    }

    /**
     * Check customer password
     *
     * @param CustomerInterface $customer
     * @param string $password
     * @return bool
     * @throws UserLockedException|InvalidEmailOrPasswordException
     */
    public function checkCustomerPassword($customer, $password)
    {
        $this->authentication->authenticate($customer->getId(), $password);
    }

    /**
     * Find customer and create if not exist
     *
     * @param $customerData
     * @return CustomerInterface|Customer|Redirect|(Redirect&ResultInterface)|ResultInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     * @throws \Zend_Log_Exception
     */
    public function createAndGetCustomer($customerData)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/kakao_customer_create.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("=============== create And Get Customer Start================");

        try {
            $logger->info("Try get customer by email");
            $customer = $this->getCustomerByEmail($customerData['email']);
        } catch (\Throwable $throwable) {
            $logger->info("catch get customer by email");
            $customer = null;
        }
        if (!$customer) {
            $logger->info("if not customer exists");


            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');
            $isLoggedIn = $customerSession->isLoggedIn();
            $customerLogin =$this->httpContext->setValue(
                \Magento\Customer\Model\Context::CONTEXT_AUTH,
                $isLoggedIn,
                false
            );
            if ($customerLogin) {
                $logger->info("----Customer session login----");
                $sessionEmail = $customerSession->getCustomer()->getEmail();
                $logger->info("session customer id is: " . $customerSession->getId());
                $logger->info("session email is 181 : " . $sessionEmail);
                $logger->info("session website id : " . $customerSession->getCustomer()->getWebsiteId());
                if ($sessionEmail) {
                    $logger->info("if not session Email exists 183");
                    $customerEmail = $this->customerFactory->create()
                        ->setWebsiteId($customerSession->getCustomer()->getWebsiteId())
                        ->loadByEmail($customerData['email']);
                    $logger->info("=====Customer Data Start=====");
                    $logger->info(print_r($customerEmail, true));
                    $logger->info("=====Customer Data End=====");
                    if (!$customerEmail) {
                        $logger->info("Get Customer by email 186");
                        $customer = $this->customerRepository->getById($this->customerSession->getId());
                        $logger->info("customer id: " . $customer->getId());
                        $logger->info("customer email: " . $customerData['email']);
                        $customer->setEmail($customerData['email']);
                        if (isset($customerData['firstname'])) {
                            $customer->setFirstname($customerData['firstname']);
                            $logger->info("customer First name: " . $customerData['firstname']);
                        }

                        if (isset($customerData['lastname'])) {
                            $customerData['lastname'] = $customerData['lastname'] ?? '';
                            $customer->setLastname($customerData['lastname']);
                            $logger->info("customer last name: " . $customerData['lastname']);
                        }
                        // Update DOB
                        if (isset($customerData['dob'])) {
                            $customer->setDob($customerData['dob']);
                            $logger->info("customer Birthday: " . $customerData['dob']);
                        }

                        // Update Gender
                        if (isset($customerData['gender']) && in_array($customerData['gender'], [1, 2])) {
                            $customer->setGender($customerData['gender']);
                            $logger->info("Customer Gender: " . $customerData['gender']);
                        } else {
                            $customer->setGender(null); // Or set a default value if needed
                            $logger->info("Customer Gender not set or invalid.");
                        }

                        // Update Phone number
                        if (isset($customerData['cell_phone'])) {
                            if (!empty($customerData['cell_phone'])) {
                                $customer->setCustomAttribute('cell_phone', $customerData['cell_phone']);
                                $logger->info("Customer Cell Phone: " . $customerData['cell_phone']);
                            } else {
                                $customer->setCustomAttribute('cell_phone', '');
                                $logger->info("Customer Cell Phone is empty");
                            }
                        }
                        $this->customerRepository->save($customer);
                        $customerSession->logout();
                        $this->messageManager->addSuccessMessage(__('Your email has been updated. Please logged in with your updated email.'));
                    } else {
                        if ($sessionEmail != $customerData['email']) {
                            throw new LocalizedException(
                                __("A customer with the same email address already exists in an associated website")
                            );
                        }
                        $customer = $this->createNewCustomer($customerData);
                    }
                } else {
                    $logger->info("Session customer is not logged in");
                }
            } else {
                $logger->info("Else sessionEmail");
                $customer = $this->createNewCustomer($customerData);
            }
        }
        if ($customer) {
            $logger->info("if customer in seesion create new customer");
            $customer  = $this->loadCustomerModel($customerData['email']);
        }
        $logger->info("=============== create And Get Customer End================");
        return $customer;
    }

    /**
     * Convert customer intergace to customer model
     *
     * @param CustomerInterface $customer
     * @return Customer
     */
    public function convertToCustomerModel($customer)
    {
        return $this->customerFactory->create()->updateData($customer);
    }

    /**
     * Load customer model object
     *
     * @param string $email
     * @return Customer
     * @throws LocalizedException
     */
    public function loadCustomerModel($email)
    {
        return $this->customerFactory->create()
            ->setWebsiteId($this->kakao->getConfigHelper()->getWebsiteId())
            ->loadByEmail($email);
    }

    /**
     * Extract Customer From Array
     *
     * @param array $data
     * @return CustomerInterface
     */
    protected function extractCustomerFromArray($data)
    {
        return $this->customerExtractor->extractFromArray('customer_account_create', $data);
    }

    /**
     * Create new customer
     *
     * @param array $customerData
     * @return CustomerInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function createNewCustomer($customerData)
    {
        $customer = null;
        $fullName = trim($customerData['name']);
        $nameParts = explode(' ', $fullName, 2);

        $customerData['firstname'] = $nameParts[0];
        $customerData['lastname'] = isset($nameParts[1]) ? $nameParts[1] : '';
        $customer = $this->extractCustomerFromArray($customerData);
        $password =  $customerData['pw'];
        $redirectUrl =  '';
        $customer = $this->accountManagement->createAccount($customer, $password, $redirectUrl);
        $customer = $this->customerRepository->getById($customer->getId());
        $groupId = $this->configHelper->getCustomerGroupId();
        $customer->setGroupId($groupId);
        $customer->setCustomAttribute('is_kakao_login', 1);
        $this->customerRepository->save($customer);

        $this->eventManager->dispatch(
            'customer_register_success',
            ['account_controller' => null, 'customer' => $customer]
        );

        return $customer;
    }

    /**
     * Save customer token data
     *
     * @param int $customerId
     * @param int $kakaoCustomerId
     * @param array $tokenData
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     * @throws LocalizedException
     */
    public function saveTokenData($customerId, $kakaoCustomerId, $tokenData)
    {
        if ($customerId) {
            try {
                $accessToken = $this->accessTokenRepository->getByCustomerId($customerId);
            } catch (NoSuchEntityException $suchEntityException) {
                $accessToken = $this->accessTokenRepository->createEmpty();
            }
        } elseif ($kakaoCustomerId) {
            try {
                $accessToken = $this->accessTokenRepository->getByKakaoCustomerId($kakaoCustomerId);
            } catch (NoSuchEntityException $suchEntityException) {
                $accessToken = $this->accessTokenRepository->createEmpty();
            }
        } else {
            $accessToken = $this->accessTokenRepository->createEmpty();
        }

        $accessToken->setCustomerId($customerId);
        $accessToken->setKakaoCustomerId($kakaoCustomerId);
        $accessToken->setAccessToken($tokenData['access_token']);
        $accessToken->setTokenType($tokenData['token_type']);
        $accessToken->setRefreshToken($tokenData['refresh_token']);
        $accessToken->setIdToken($tokenData['id_token']);
        $accessToken->setExpiresIn($tokenData['expires_in']);
        $accessToken->setScope($tokenData['scope']);
        $accessToken->setRefreshTokenExpiresIn($tokenData['refresh_token_expires_in']);

        return $this->accessTokenRepository->save($accessToken);
    }
}
