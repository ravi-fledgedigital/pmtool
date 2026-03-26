<?php
namespace Seoulwebdesign\KakaoSync\Block\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Seoulwebdesign\KakaoSync\Helper\ConfigHelper;
use Seoulwebdesign\KakaoSync\Model\AccessTokenRepository;
use Seoulwebdesign\KakaoSync\Service\Kakao;
use Seoulwebdesign\KakaoSync\Service\Address as KakaoAddress;

class Link extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var AccessTokenRepository
     */
    protected $accessTokenRepository;
    /**
     * @var Kakao
     */
    protected $kakaoService;
    /**
     * @var string|null
     */
    protected $token;
    /**
     * @var KakaoAddress
     */
    protected $kakaoAddress;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @param Template\Context $context
     * @param Session $customerSession
     * @param AccessTokenRepository $accessTokenRepository
     * @param Kakao $kakaoService
     * @param KakaoAddress $kakaoAddress
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $customerSession,
        AccessTokenRepository $accessTokenRepository,
        Kakao $kakaoService,
        KakaoAddress  $kakaoAddress,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->kakaoService = $kakaoService;
        $this->kakaoAddress = $kakaoAddress;
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * Initial
     */
    protected function init()
    {
        if (!$this->token) {
            $this->customer = $this->customerSession->getCustomer();
            $customerId = $this->customerSession->getCustomer()->getId();
            try {
                $currentToken = $this->accessTokenRepository->getByCustomerId($customerId);
                $this->token = $currentToken->getAccessToken();
            } catch (\Throwable $t) {
                $this->configHelper->getLogger()->logError($t->getMessage(), 'CustomerLink');
                $this->token = '';
            }
        }
    }

    /**
     * Get link status
     *
     * @return false|mixed
     */
    public function getLinkStatus()
    {
        $this->init();
        $term  =$this->kakaoService->getTerms($this->token);
        return $this->kakaoService->getConsentDetails($this->token);
    }

    /**
     * Get shipping address
     *
     * @return array|mixed
     */
    public function getShippingAddress()
    {
        $this->init();
        return $this->kakaoAddress->setToken($this->token)->pullAddress();
    }

    /**
     * Get import url
     *
     * @param string $addressId
     * @return string
     */
    public function getImportUrl($addressId)
    {
        return $this->getUrl('kakaosync/customer/importAddress', ['id' => $addressId]);
    }

    /**
     * Get redirect auth link
     *
     * @return string
     */
    public function getRediectOAuthLink()
    {
        return $this->configHelper->getRedirectUrl();
    }
}
