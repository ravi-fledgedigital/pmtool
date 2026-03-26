<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Controller\Customer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Seoulwebdesign\KakaoSync\Model\AccessTokenRepository;
use Seoulwebdesign\KakaoSync\Service\Address as KakaoAddress;
use Seoulwebdesign\KakaoSync\Service\Kakao;

class ImportAddress extends \Seoulwebdesign\KakaoSync\Controller\Manage
{
    /**
     * @var KakaoAddress
     */
    protected $kakaoAddress;
    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;
    /**
     * @var AddressInterfaceFactory
     */
    protected $addressDataFactory;
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccessTokenRepository $accessTokenRepository
     * @param Kakao $kakaoService
     * @param KakaoAddress $kakaoAddress
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressDataFactory
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccessTokenRepository $accessTokenRepository,
        Kakao $kakaoService,
        KakaoAddress $kakaoAddress,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        UrlInterface $urlInterface
    ) {
        parent::__construct($context, $customerSession, $accessTokenRepository, $kakaoService);
        $this->kakaoAddress = $kakaoAddress;
        $this->addressRepository = $addressRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->urlInterface = $urlInterface;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * Managing newsletter subscription page
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->init();
        $id = $this->getRequest()->getParam('id');
        $allAddress = $this->kakaoAddress->setToken($this->token)->pullAddress();
        $url = $this->urlInterface->getUrl('kakaosync/customer/link');
        $resultRedirect->setUrl($url);
        foreach ($allAddress as $address) {
            if ($id==$address['id']) {
                try {
                    $this->saveAddress($address);
                    $this->messageManager->addSuccessMessage('Address import success!');
                    return $resultRedirect;
                } catch (\Throwable $t) {
                    $this->messageManager->addErrorMessage($t->getMessage());
                    return $resultRedirect;
                }
            }
        }
        $this->messageManager->addNoticeMessage('There is no address');
        return $resultRedirect;
    }

    /**
     * Save the address
     *
     * @param array $address
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function saveAddress($address)
    {
        $customerAddress = $this->addressDataFactory->create();
        $parts = explode(' ', $address['receiver_name']);
        $lastName = end($parts);
        $firstName = trim(str_replace($lastName, '', $address['receiver_name']));
        if (!$firstName) {
            $firstName = $lastName;
            $lastName = '-';
        }
        $countryId = 'KR';
        $regionId = '';
        $regionName = '';
        $city = '-';//Required
        $postcode = $address['zone_number'];
        $customerId = (int)$this->customer->getEntityId();
        $street1 = $address['base_address'];
        $street2 = $address['detail_address'];
        $telephone = $address['receiver_phone_number1'];
        $customerAddress->setFirstname($firstName)
            ->setLastname($lastName)
            ->setCountryId($countryId)
            ->setRegionId($regionId)
            //->setRegion($regionName)
            ->setCity($city)
            ->setPostcode($postcode)
            ->setCustomerId($customerId)
            ->setStreet([$street1,$street2])
            ->setTelephone($telephone);

        $this->addressRepository->save($customerAddress);
    }
}
