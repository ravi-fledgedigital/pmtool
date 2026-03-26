<?php

declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Observer\Frontend\Controller;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Seoulwebdesign\KakaoSync\Api\AccessTokenRepositoryInterface;
use Seoulwebdesign\KakaoSync\Service\Kakao;

class ActionPredispatchCustomerAccountLogout implements ObserverInterface
{
    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;
    /**
     * @var Kakao
     */
    private $kakao;
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $redirect;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $resultRedirectFactory;
    /**
     * @var ResponseFactory
     */
    private $responseFactory;
    /**
     * @var ActionFlag
     */
    private $actionFlag;
    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * The constructor
     *
     * @param Context $context
     * @param Session $customerSession
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     * @param Kakao $kakao
     * @param ResponseFactory $responseFactory
     * @param ActionFlag $actionFlag
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccessTokenRepositoryInterface $accessTokenRepository,
        Kakao $kakao,
        ResponseFactory $responseFactory,
        ActionFlag $actionFlag,
        UrlInterface $urlInterface
    ) {
        $this->customerSession = $customerSession;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->kakao = $kakao;
        $this->responseFactory = $responseFactory;
        $this->actionFlag = $actionFlag;
        $this->redirect = $context->getRedirect();
        $this->urlInterface = $urlInterface;
    }
    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        Observer $observer
    ) {
        $lastCustomerId = $this->customerSession->getId();
        try {
            $accessToken = $this->accessTokenRepository->getByCustomerId($lastCustomerId);

            if ($accessToken->getAccessTokenId() && $accessToken->getAccessToken()) {
                $state = base64_encode($this->redirect->getRefererUrl());
                $url = $this->urlInterface->getUrl('kakaosync/redirect/logout');
                $redirectionUrl = $this->kakao->getLogoutUrl($url, $state);
                //$this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();

                $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);

                /** @var \Magento\Framework\App\Action\Action $controller */
                $controller = $observer->getControllerAction();
                $this->redirect->redirect($controller->getResponse(), $redirectionUrl);
            }
        } catch (NoSuchEntityException $noSuchEntityException) {
            return $this;
        }
        return $this;
        //Your observer code
    }
}
