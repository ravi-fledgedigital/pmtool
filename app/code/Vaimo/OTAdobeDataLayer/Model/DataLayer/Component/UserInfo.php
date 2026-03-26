<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTAdobeDataLayer\Model\DataLayer\Component;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Vaimo\AepEventStreaming\Service\CustomerId;
use Vaimo\OTAdobeDataLayer\Api\ConfigInterface;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class UserInfo implements ComponentInterface
{
    private const CUSTOMER_ID = 'customerId';
    private const USER_HASHED_ID = 'userHashedId';
    private const USER_LOGGED_IN = 'userLoggedIn';
    private const LOGGED_IN_SITE = 'loggedInSite';
    private const LOGGED_IN_SITE_LANGUAGE = 'loggedInSiteLanguage';
    private const LOGGED_IN_REGION = 'loggedInRegion';
    private const LOGGED_IN_COUNTRY = 'loggedInCountry';

    private CustomerId $customerIdService;
    private ConfigInterface $dataLayerConfig;
    private EncryptorInterface $encryptor;
    private ScopeConfigInterface $scopeConfig;
    private SerializerInterface $serializer;
    private Session $customerSession;

    public function __construct(
        CustomerId $customerIdService,
        ConfigInterface $dataLayerConfig,
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        Session $customerSession
    ) {
        $this->customerIdService = $customerIdService;
        $this->dataLayerConfig = $dataLayerConfig;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->customerSession = $customerSession;
    }

    public function getComponentData(): string
    {
        $userInfo = [
            self::CUSTOMER_ID => $this->getCustomerId(),
            self::USER_HASHED_ID => $this->getUserHashedId(),
            self::USER_LOGGED_IN => $this->customerSession->isLoggedIn(),
            self::LOGGED_IN_SITE => $this->dataLayerConfig->getLoggedInSite(),
            self::LOGGED_IN_SITE_LANGUAGE => \strtoupper($this->getStoreLocale()),
            self::LOGGED_IN_REGION => $this->dataLayerConfig->getLoggedInRegion(),
            self::LOGGED_IN_COUNTRY => \strtoupper($this->getDefaultCountry()),
        ];

        return $this->serializer->serialize($userInfo);
    }

    private function getCustomerId(): ?string
    {
        $customerId = (int) $this->customerSession->getCustomerId();

        return $customerId ? $this->customerIdService->getById($customerId) : '';
    }

    private function getUserHashedId(): ?string
    {
        $customerId = $this->getCustomerId();

        return $customerId ? $this->encryptor->hash($customerId) : '';
    }

    private function getDefaultCountry(): ?string
    {
        return $this->scopeConfig->getValue('general/country/default', ScopeInterface::SCOPE_STORE);
    }

    private function getStoreLocale(): ?string
    {
        return $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE);
    }
}
