<?php
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Observer\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Seoulwebdesign\KakaoSync\Api\AccessTokenRepositoryInterface;
use Seoulwebdesign\KakaoSync\Service\Kakao;

class Logout implements ObserverInterface
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
     * The constructor
     *
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     * @param Kakao $kakao
     */
    public function __construct(
        AccessTokenRepositoryInterface $accessTokenRepository,
        Kakao $kakao
    ) {
        $this->accessTokenRepository = $accessTokenRepository;
        $this->kakao = $kakao;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(
        Observer $observer
    ) {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $observer->getData('customer');
        try {
            $currentToken = $this->accessTokenRepository->getByCustomerId($customer->getId());
            $this->kakao->logout($currentToken->getAccessToken());
        } catch (\Throwable $t) {
            $a =1;
        }
    }
}
