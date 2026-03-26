<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Controller\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Seoulwebdesign\KakaoSync\Api\AccessTokenRepositoryInterface;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface;
use Seoulwebdesign\KakaoSync\Helper\ConfigHelper;
use Seoulwebdesign\KakaoSync\Helper\Logger;
use Seoulwebdesign\KakaoSync\Model\CustomerAuthorize;
use Seoulwebdesign\KakaoSync\Service\Kakao;

class LinkAccountPost implements HttpPostActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Kakao
     */
    protected $kakao;
    /**
     * @var Context
     */
    protected $context;
    /**
     * @var ConfigHelper
     */
    protected $configHelper;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var CustomerAuthorize
     */
    protected $customerAuthorize;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var RedirectInterface
     */
    protected $redirect;
    /**
     * @var UrlInterface
     */
    protected $urlInterface;
    /**
     * @var AccessTokenRepositoryInterface
     */
    protected $accessTokenRepository;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     * @param Context $context
     * @param RequestInterface $request
     * @param Kakao $kakao
     * @param CustomerAuthorize $customerAuthorize
     * @param Session $session
     * @param AccountRedirect $accountRedirect
     * @param UrlInterface $urlInterface
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Context $context,
        RequestInterface $request,
        Kakao $kakao,
        CustomerAuthorize $customerAuthorize,
        Session $session,
        AccountRedirect $accountRedirect,
        UrlInterface $urlInterface,
        AccessTokenRepositoryInterface $accessTokenRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->context = $context;
        $this->request = $request;
        $this->kakao = $kakao;
        $this->customerAuthorize = $customerAuthorize;
        $this->session = $session;
        $this->accountRedirect = $accountRedirect;
        $this->urlInterface = $urlInterface;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->configHelper = $kakao->getConfigHelper();
        $this->logger = $this->configHelper->getLogger();
        $this->messageManager = $this->context->getMessageManager();
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->redirect = $context->getRedirect();
    }

    /**
     *  Execute the request
     */
    public function execute()
    {
        $kid = $this->request->getParam('kid');
        $email = $this->request->getParam('email');
        $password = $this->request->getParam('password');
        $confirmation = $this->request->getParam('password_confirmation');

        try {
            $existCustomer = $this->findExistCustomerByEmail($email);
            if ($existCustomer) {
                try {
                    $this->customerAuthorize->checkCustomerPassword($existCustomer, $password);
                } catch (InvalidEmailOrPasswordException $emailOrPasswordException) {
                    $message = __(
                        'The account with email %1 is exist, please enter your password to link the account',
                        $email
                    );
                    throw new LocalizedException($message);
                }
            }

            $this->checkPasswordConfirmation($password, $confirmation);

            $accessToken = $this->accessTokenRepository->getByKakaoCustomerId($kid);

            //$this->logger->logDebug($accessToken, 'LinkAccount');
            $userInfo = $this->kakao->getUserInfomation($accessToken->getAccessToken());
            $this->logger->logDebug($userInfo, 'LinkAccount');
            //$re = $this->kakao->unlink($tokenData['access_token']);

            $customerData = [];
            $customerData['email'] = $email;
            if (isset($userInfo['kakao_account']['profile']['nickname'])) {
                $customerData['name'] = $userInfo['kakao_account']['profile']['nickname'];
            } else {
                $customerData['name'] = __('Customer');
            }
            $customerData['pw'] = $password;
            $newCustomer = $this->customerAuthorize->createAndGetCustomer($customerData);
            return $this->doLoginAndRedirect($newCustomer, $accessToken);

        } catch (\Throwable $t) {
            $this->messageManager->addErrorMessage($t->getMessage());
            $this->logger->logError($t->getMessage(), 'LinkAccount');
            $resultRedirect = $this->resultRedirectFactory->create();
            $param = [];
            $param['kid'] = $kid;
            $param['email'] = $email;
            $url = $this->urlInterface->getUrl('kakaosync/customer/linkaccount', $param);
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }
    }

    /**
     * Find customer by email
     *
     * @param string $email
     * @return CustomerInterface|null
     * @throws LocalizedException
     */
    protected function findExistCustomerByEmail($email)
    {
        try {
            $customer = $this->customerAuthorize->getCustomerByEmail($email);
        } catch (NoSuchEntityException $noSuchEntityException) {
            $customer = null;
        }
        return $customer;
    }
    /**
     * Do login and redirect
     *
     * @param CustomerInterface $customer
     * @param AccessTokenInterface $accessToken
     * @return \Magento\Framework\Controller\Result\Redirect|void
     * @throws LocalizedException
     */
    protected function doLoginAndRedirect($customer, $accessToken)
    {
        $accessToken->setCustomerId($customer->getId());
        $this->accessTokenRepository->save($accessToken);
        $customerModel = $this->customerAuthorize->convertToCustomerModel($customer);
        $this->session->setCustomerAsLoggedIn($customerModel);
        $redirectUrl = $this->accountRedirect->getRedirectCookie();
        if (!$this->configHelper->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {
            $this->accountRedirect->clearRedirectCookie();
            $resultRedirect = $this->resultRedirectFactory->create();
            // URL is checked to be internal in $this->_redirect->success()
            $resultRedirect->setUrl($this->redirect->success($redirectUrl));
            return $resultRedirect;
        }
        return $this->accountRedirect->getRedirect();
    }

    /**
     * Check confirm password
     *
     * @param string $password
     * @param string $confirmation
     * @throws InputException
     */
    protected function checkPasswordConfirmation($password, $confirmation)
    {
        if ($password != $confirmation) {
            throw new InputException(__('Please make sure your passwords match.'));
        }
    }
}
