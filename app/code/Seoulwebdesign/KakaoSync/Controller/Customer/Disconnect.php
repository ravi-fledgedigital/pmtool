<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Controller\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Seoulwebdesign\KakaoSync\Model\AccessTokenRepository;
use Seoulwebdesign\KakaoSync\Service\Kakao;

class Disconnect extends \Seoulwebdesign\KakaoSync\Controller\Manage
{

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccessTokenRepository $accessTokenRepository
     * @param Kakao $kakaoService
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccessTokenRepository $accessTokenRepository,
        Kakao $kakaoService,
        UrlInterface $urlInterface
    ) {
        parent::__construct($context, $customerSession, $accessTokenRepository, $kakaoService);
        $this->urlInterface = $urlInterface;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * Managing newsletter subscription page
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $this->init();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->token) {
            $re = $this->kakaoService->unlink($this->token);
            if ($re) {
                $this->accessTokenRepository->delete($this->currentToken);
                $this->messageManager->addNoticeMessage('KakaoSync Disconnected');
                $url = $this->urlInterface->getUrl('kakaosync/customer/link');
                $resultRedirect->setUrl($url);
            }
        } else {
            $this->messageManager->addWarningMessage('Token Invalid');
            $url = $this->urlInterface->getUrl('kakaosync/customer/link');
            $resultRedirect->setUrl($url);
        }
        return $resultRedirect;
    }
}
