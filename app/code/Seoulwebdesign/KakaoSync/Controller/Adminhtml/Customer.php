<?php
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\JsonFactory;
use Seoulwebdesign\KakaoSync\Model\AccessTokenRepository;
use Seoulwebdesign\KakaoSync\Service\Kakao;

/**
 * Class Customer
 * @package Seoulwebdesign\KakaoSync\Controller\Adminhtml
 * @codeCoverageIgnore
 */
abstract class Customer extends Action
{

    public const ADMIN_RESOURCE = 'Seoulwebdesign_KakaoSync::Customer';
    /**
     * @var Registry
     */
    protected $_coreRegistry;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var AccessTokenRepository
     */
    protected $accessTokenRepository;
    /**
     * @var Kakao
     */
    protected $kakaoService;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JsonFactory $resultJsonFactory
     * @param AccessTokenRepository $accessTokenRepository
     * @param Kakao $kakaoService
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JsonFactory $resultJsonFactory,
        AccessTokenRepository $accessTokenRepository,
        Kakao $kakaoService
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->kakaoService = $kakaoService;
        parent::__construct($context);
    }

    /**
     * Check is allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
