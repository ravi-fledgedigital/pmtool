<?php
/** phpcs:ignoreFile */

/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Controller\Redirect;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Seoulwebdesign\KakaoSync\Api\AccessTokenRepositoryInterface;
use Seoulwebdesign\KakaoSync\Model\CustomerAuthorize;
use Seoulwebdesign\KakaoSync\Service\Kakao;

class Oauth implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var Kakao
     */
    protected $kakao;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;
    /**
     * @var AccountRedirect
     */
    private $accountRedirect;
    /**
     * @var \Seoulwebdesign\KakaoSync\Helper\ConfigHelper
     */
    private $configHelper;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $resultRedirectFactory;
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $_redirect;
    /**
     * @var \Seoulwebdesign\KakaoSync\Helper\Logger
     */
    private $logger;
    /**
     * @var UrlInterface
     */
    protected $urlInterface;
    /**
     * @var CustomerAuthorize
     */
    protected $customerAuthorize;
    /**
     * @var AccessTokenRepositoryInterface
     */
    protected $accessTokenRepository;
    private \Magento\Customer\Api\AccountManagementInterface $accountManagement;
    private CustomerRepositoryInterface $customerRepository;
    private \Magento\Framework\App\Http\Context $httpContext;
    private ResultFactory $resultFactory;
    private \Magento\Customer\Model\CustomerFactory $customerFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param PageFactory $resultPageFactory
     * @param Kakao $kakao
     * @param Session $session
     * @param AccountRedirect $accountRedirect
     * @param UrlInterface $urlInterface
     * @param CustomerAuthorize $customerAuthorize
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        PageFactory $resultPageFactory,
        Kakao $kakao,
        Session $session,
        \Magento\Framework\App\Http\Context $httpContext,
        AccountRedirect $accountRedirect,
        UrlInterface $urlInterface,
        CustomerAuthorize $customerAuthorize,
        AccessTokenRepositoryInterface $accessTokenRepository,
        CustomerRepositoryInterface $customerRepository,
        ResultFactory $resultFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement
    ) {
        $this->context = $context;
        $this->request = $request;
        $this->resultPageFactory = $resultPageFactory;
        $this->kakao = $kakao;
        $this->session = $session;
        $this->accountRedirect = $accountRedirect;
        $this->urlInterface = $urlInterface;
        $this->customerAuthorize = $customerAuthorize;
        $this->accessTokenRepository = $accessTokenRepository;

        $this->messageManager = $this->context->getMessageManager();
        $this->configHelper = $kakao->getConfigHelper();
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->_redirect = $context->getRedirect();
        $this->logger = $this->configHelper->getLogger();
        $this->accountManagement = $accountManagement;
        $this->customerRepository = $customerRepository;
        $this->httpContext = $httpContext;
        $this->resultFactory = $resultFactory;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/oauth.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("==========oauth execute=========");

        //http://ce24.magevn.com/kakaosync/redirect/oauth?code=G4HNcyXGxn25NPfA0EMXN1LUW-0_EB-pK0Yl_a7wdIhfGBgLT6Hav57MV5Z8a3mVfB8zwAo9dRsAAAGAlSoqEg
        $code = $this->request->getParam('code');
        $error = $this->request->getParam('error');
        $error_description = $this->request->getParam('error_description');
        $state = $this->request->getParam('state');
        $this->logger->logDebug($this->request->getParams(), 'Oauth');

        if ($error || !$code) {
            $logger->info("inside if (error || !code)....");
            //https://developers.kakao.com/docs/latest/en/kakaologin/rest-api#request-code
            switch ($error) {
                /*
                 * This error is returned if clicks [Cancel] on the Consent screen instead of [Accept and Continue].
                 * Implement the subsequent process, such as redirecting the user to the main page.
                 * This error is returned if the parental consent for a user under the age of 14 fails.
                 * Implement the subsequent process, such as redirecting the user to the main page.
                 */
                case 'access_denied':
                    $this->messageManager->addErrorMessage('Failed to sign in');
                    break;
                case 'login_required':
                    $this->messageManager->addErrorMessage('You need to login');
                    break;
                case 'consent_required':
                    $this->messageManager->addErrorMessage('consent_required');
                    break;
                case 'interaction_required':
                    $this->messageManager->addErrorMessage('interaction_required');
                    break;
            }
            return $this->accountRedirect->getRedirect();
        }

        //https://developers.kakao.com/docs/latest/en/kakaologin/rest-api#request-token
        try {
            $logger->info("inside try....182");
            $tokenData = $this->kakao->getToken($code);
            if ($tokenData) {
                $this->logger->logDebug($tokenData, 'Oauth');
                //getUser infor
                $userInfo = $this->kakao->getUserInfomation($tokenData['access_token']);
                $this->logger->logDebug($userInfo, 'Oauth');
                //$re = $this->kakao->unlink($tokenData['access_token']);
                $kakaoCustomerId = $userInfo['id'];
                $email = isset($userInfo['kakao_account']['email']) ? $userInfo['kakao_account']['email'] : '';
                //$email = '';
                $existCustomer = null;

                $logger->info("inside try....195 customer email : {$email}");

                if ($email) {
                    $logger->info("inside try if(email)....198");
                    try {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
                        $isLoggedIn = $customerSession->isLoggedIn();
                        $customerLogin =$this->httpContext->setValue(
                            \Magento\Customer\Model\Context::CONTEXT_AUTH,
                            $isLoggedIn,
                            false
                        );
                        if ($customerLogin) {
                            $logger->info("----if not customer exists on kakao----");
                            $sessionEmail = $customerSession->getCustomer()->getEmail();
                            $logger->info("session email is : " . $sessionEmail);
                            $logger->info("session customer id is: " . $customerSession->getId());
                            $logger->info("session website id is: " . $customerSession->getCustomer()->getWebsiteId());
                            if ($sessionEmail) {
                                $logger->info("if not session Email exists");
                                $customerEmail = $this->customerFactory->create()
                                    ->setWebsiteId($customerSession->getCustomer()->getWebsiteId())
                                    ->loadByEmail($email);
                                $logger->info("=====Customer Data Start=====");
                                $logger->info(print_r($customerEmail, true));
                                $logger->info("=====Customer Data End=====");
                                if (!$customerEmail) {
                                    $logger->info("Get Customer by email");
                                    $customer = $this->customerRepository->getById($customerSession->getId());
                                    $logger->info("customer id: " . $customer->getId());
                                    $logger->info("session customer id: " . $customerSession->getId());
                                    $logger->info("customer email: " . $customer->getEmail());
                                    $logger->info("customer updated email: " . $email);
                                    $customer->setEmail($email);

                                    // Update Name
                                    if (isset($userInfo['kakao_account']['name'])) {
                                        $fullName = trim($userInfo['kakao_account']['name']);
                                        $nameParts = explode(' ', $fullName, 2);

                                        $customerData['firstname'] = $nameParts[0];
                                        $customerData['lastname'] = isset($nameParts[1]) ? $nameParts[1] : '';
                                        $customer->setFirstname($customerData['firstname']);
                                        $customer->setLastname($customerData['lastname']);
                                        $logger->info("customer First name: " . $customerData['firstname']);
                                        $logger->info("customer last name: " . $customerData['lastname']);
                                    }

                                    // Update DOB
                                    if (isset($userInfo['kakao_account']['birthyear']) && isset($userInfo['kakao_account']['birthday'])) {
                                        $birthYear = $userInfo['kakao_account']['birthyear'];
                                        $birthday = $userInfo['kakao_account']['birthday'];
                                        $formattedBirthday = substr($birthday, 0, 2) . '/' . substr($birthday, 2, 2);
                                        $customerData['dob'] = $birthYear . '/' . $formattedBirthday;
                                        $customer->setDob($customerData['dob']);
                                        $logger->info("customer Birthday: " . $customerData['dob']);
                                    }

                                    // Update Gender
                                    if (isset($userInfo['kakao_account']['gender'])) {
                                        if ($userInfo['kakao_account']['gender'] == 'female') {
                                            $customerData['gender'] = 2;
                                        }
                                        if ($userInfo['kakao_account']['gender'] == 'male') {
                                            $customerData['gender'] = 1;
                                        }
                                    } else {
                                        $customerData['gender'] = ' ';
                                    }
                                    $customer->setGender($customerData['gender']);
                                    $logger->info("customer Gender: " . $customerData['gender']);

                                    // Update Phone number
                                    if (!empty($userInfo['kakao_account']['phone_number'])) {
                                        $phoneNumber = trim($userInfo['kakao_account']['phone_number']);
                                        $logger->info('Cell Phone Data Kakao: ' . $phoneNumber);

                                        $parts = explode(' ', $phoneNumber, 2);

                                        if (isset($parts[1])) {
                                            $mobileNumber = preg_replace('/\D/', '', $parts[1]);
                                            if (!empty($mobileNumber) && $mobileNumber[0] != '0') {
                                                $mobileNumber = '0' . $mobileNumber;
                                            }
                                            $customerData['cell_phone'] = trim($mobileNumber);
                                            $logger->info('Cell Phone Data: ' . $customerData['cell_phone']);
                                        } else {
                                            $customerData['cell_phone'] = trim($phoneNumber);
                                        }
                                        $logger->info('Final Cell Phone Data: ' . $customerData['cell_phone']);
                                    } else {
                                        $customerData['cell_phone'] = '';
                                    }
                                    $customer->setCustomAttribute('cell_phone', $customerData['cell_phone']);
                                    $this->customerRepository->save($customer);
                                    $customerSession->logout();
                                    $this->messageManager->addSuccessMessage(__('Your email has been updated. Please log in with your updated email.'));
                                } else {
                                    if ($sessionEmail != $email) {
                                        throw new LocalizedException(
                                            __("A customer with the same email address already exists in an associated website")
                                        );
                                    }
                                }
                            } else {
                                $logger->info("Else sessionEmail");
                            }
                        } else {
                            $logger->info("Else session Not logged in");
                        }

                        $existCustomer = $this->customerAuthorize->getCustomerByEmail($email);
                        $logger->info("inside try if(email) try....229 exist customer id: " . $existCustomer->getId());
                    } catch (LocalizedException $e) {
                        $existCustomer = null;
                        $logger->info("inside try if(email) catch....193 existcustomer null: " . $e->getMessage());
                    }
                } else {
                    $logger->info("inside try if(email)....235");
                    $existCustomer = $this->findCustomerByKakaoCustomerId($kakaoCustomerId);
                }

                if ($existCustomer) {
                    $logger->info("inside try  if (existCustomer)....240");
                    return $this->doLoginAndRedirect($existCustomer, $kakaoCustomerId, $tokenData);
                } else {
                    $logger->info("inside try else (existCustomer)....243");
                    if ($email) {
                        $logger->info("inside try else (existCustomer) if (email) ....245\n");

                        if (isset($userInfo['kakao_account']['birthyear']) && isset($userInfo['kakao_account']['birthday'])) {
                            $birthYear = $userInfo['kakao_account']['birthyear'];
                            $birthday = $userInfo['kakao_account']['birthday'];
                            $formattedBirthday = substr($birthday, 0, 2) . '/' . substr($birthday, 2, 2);
                            $customerData['dob'] = $birthYear . '/' . $formattedBirthday;
                        }
                        if (isset($userInfo['kakao_account']['name'])) {
                            $fullName = trim($userInfo['kakao_account']['name']);
                            $nameParts = explode(' ', $fullName, 2);

                            $customerData['firstname'] = $nameParts[0];
                            $customerData['lastname'] = isset($nameParts[1]) ? $nameParts[1] : '';
                        }

                        if (isset($userInfo['kakao_account']['gender'])) {
                            if ($userInfo['kakao_account']['gender'] == 'female') {
                                $customerData['gender'] = 2;
                            }
                            if ($userInfo['kakao_account']['gender'] == 'male') {
                                $customerData['gender'] = 1;
                            }
                        } else {
                            $customerData['gender'] = ' ';
                        }

                        if (!empty($userInfo['kakao_account']['phone_number'])) {
                            $phoneNumber = trim($userInfo['kakao_account']['phone_number']);
                            $logger->info('Cell Phone Data Kakao: ' . $phoneNumber);

                            $parts = explode(' ', $phoneNumber, 2);

                            if (isset($parts[1])) {
                                $mobileNumber = preg_replace('/\D/', '', $parts[1]);
                                if (!empty($mobileNumber) && $mobileNumber[0] != '0') {
                                    $mobileNumber = '0' . $mobileNumber;
                                }
                                $customerData['cell_phone'] = trim($mobileNumber);
                                $logger->info('Cell Phone Data: ' . $customerData['cell_phone']);
                            } else {
                                $customerData['cell_phone'] = trim($phoneNumber);
                            }
                            $logger->info('Final Cell Phone Data: ' . $customerData['cell_phone']);
                        } else {
                            $customerData['cell_phone'] = '';
                        }

                        $customerData['email'] = $email;
                        if (isset($userInfo['kakao_account']['profile']['nickname'])) {
                            $customerData['name'] = $userInfo['kakao_account']['profile']['nickname'];
                        } else {
                            $customerData['name'] = $userInfo['kakao_account']['name'];
                        }
                        $customerData['pw'] = $tokenData['access_token'];

                        $logger->info("=====Create New Customer Data Start=====");
                        $logger->info(print_r($customerData, true));
                        $logger->info("=====Create New Customer Data End=====");

                        $newCustomer = $this->customerAuthorize->createAndGetCustomer($customerData);
                        return $this->doLoginAndRedirect($newCustomer, $kakaoCustomerId, $tokenData);
                    } else {
                        $logger->info("inside try else (existCustomer) else (email)  ....308");
                        $this->customerAuthorize->saveTokenData(null, $kakaoCustomerId, $tokenData);
                        $resultRedirect = $this->resultRedirectFactory->create();
                        //$param = $this->request->getParams();
                        $param = [];
                        //$param['code'] = $code;
                        $param['kid'] = $kakaoCustomerId;
                        $url = $this->urlInterface->getUrl('kakaosync/customer/linkaccount', $param);
                        $resultRedirect->setUrl($url);
                        return $resultRedirect;
                    }
                }
            } else {
                $logger->info("else ....321");
                $message =__('Failed to get token');
                $this->messageManager->addErrorMessage($message);
                $this->logger->logError($message, 'Oauth');
            }
        } catch (\Throwable $t) {
            $this->logger->logError($t->getMessage(), 'Oauth');
            $this->messageManager->addErrorMessage($t->getMessage());
        }
        $logger->info("==========outh execute end=========");
        return $this->accountRedirect->getRedirect();
    }

    /**
     * Find Customer By Kakao CustomerId
     *
     * @param int $kakaoCustomerId
     * @return CustomerInterface|null
     * @throws LocalizedException
     */
    protected function findCustomerByKakaoCustomerId($kakaoCustomerId)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/oauth.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("==========find Customer By Kakao CustomerId=========");
        $customer = null;
        try {
            $logger->info("==========find Customer By Kakao CustomerId try=========");
            $existToken = $this->accessTokenRepository->getByKakaoCustomerId($kakaoCustomerId);
            $customerId = $existToken->getCustomerId();
        } catch (NoSuchEntityException $noSuchEntityException) {
            $customerId = null;
        }

        if ($customerId) {
            try {
                $logger->info("==========find Customer By Kakao CustomerId try if (customerId)=========");

                $customer = $this->customerAuthorize->getCustomer($customerId);
            } catch (NoSuchEntityException $noSuchEntityException) {
                //Remove invalid token
                $this->accessTokenRepository->delete($existToken);
            }
        }
        return $customer;
    }

    /**
     * Do login and redirect
     *
     * @param CustomerInterface $customer
     * @param int $kakaoCustomerId
     * @param array $tokenData
     * @return \Magento\Framework\Controller\Result\Redirect|void
     * @throws LocalizedException
     */
    protected function doLoginAndRedirect($customer, $kakaoCustomerId, $tokenData)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/oauth.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("==========do Login And Redirect=========");
        $this->customerAuthorize->saveTokenData($customer->getId(), $kakaoCustomerId, $tokenData);
        $customerModel = $this->customerAuthorize->convertToCustomerModel($customer);
        $this->session->setCustomerAsLoggedIn($customerModel);
        $redirectUrl = $this->accountRedirect->getRedirectCookie();
        if (!$this->configHelper->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {
            $logger->info("==========do Login And Redirect inside if=========");
            $this->accountRedirect->clearRedirectCookie();
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->_redirect->success($redirectUrl));
            return $resultRedirect;
        }
        return $this->accountRedirect->getRedirect();
    }

    public function sendEmailConfirmation($customer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/oauth.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        try {
            $this->accountManagement->resendConfirmation($customer->getEmail(), $customer->getWebsiteId());
            $logger->info('sending email confirmation: %1', $customer->getEmail());
        } catch (\Exception $e) {
            $logger->info('Error sending email confirmation: %1', $e->getMessage());
        }
    }
}
