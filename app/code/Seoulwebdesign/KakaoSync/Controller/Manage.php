<?php
namespace Seoulwebdesign\KakaoSync\Controller;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface;
use Seoulwebdesign\KakaoSync\Model\AccessTokenRepository;
use Seoulwebdesign\KakaoSync\Service\Kakao;

/**
 * Customers newsletter subscription controller
 */
abstract class Manage extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var Customer
     */
    protected $customer;
    /**
     * @var string
     */
    protected $token;
    /**
     * @var AccessTokenRepository
     */
    protected $accessTokenRepository;
    /**
     * @var Kakao
     */
    protected $kakaoService;
    /**
     * @var AccessTokenInterface
     */
    protected $currentToken;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccessTokenRepository $accessTokenRepository
     * @param Kakao $kakaoService
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccessTokenRepository $accessTokenRepository,
        Kakao $kakaoService
    ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->kakaoService = $kakaoService;
    }

    /**
     * Check customer authentication for some actions
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\SessionException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_customerSession->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

    /**
     * Initial
     *
     * @throws LocalizedException
     */
    protected function init()
    {
        if (!$this->token) {
            $this->customer = $this->_customerSession->getCustomer();
            $customerId = $this->_customerSession->getCustomer()->getId();
            $this->currentToken = $this->accessTokenRepository->getByCustomerId($customerId);
            $this->token = $this->currentToken->getAccessToken();
        }
    }
}
