<?php

namespace Seoulwebdesign\KakaoSync\Controller\Redirect;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Seoulwebdesign\KakaoSync\Api\AccessTokenRepositoryInterface;

class Logout implements HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $redirect;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;
    /**
     * @var UrlInterface
     */
    private $urlInterface;
    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;

    /**
     * The contructor
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param Session $customerSession
     * @param UrlInterface $urlInterface
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        Session $customerSession,
        UrlInterface $urlInterface,
        AccessTokenRepositoryInterface $accessTokenRepository
    ) {
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->urlInterface = $urlInterface;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->redirect = $context->getRedirect();
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
    }

    /**
     * Main execute
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $refererUrl = base64_decode($this->request->getParam('state'));
        $lastCustomerId = $this->customerSession->getId();
        try {
            $accessToken = $this->accessTokenRepository->getByCustomerId($lastCustomerId);
            $accessToken->setAccessToken('');
            $this->accessTokenRepository->save($accessToken);
        } catch (NoSuchEntityException $noSuchEntityException) {
            //do nothing
        }
        $this->customerSession->setBeforeAuthUrl($refererUrl)
            ->setLastCustomerId($lastCustomerId);
        $resultRedirect = $this->resultRedirectFactory->create();
        $url = $this->urlInterface->getUrl('customer/account/logout');
        $resultRedirect->setUrl($url);
        return $resultRedirect;
    }
}
