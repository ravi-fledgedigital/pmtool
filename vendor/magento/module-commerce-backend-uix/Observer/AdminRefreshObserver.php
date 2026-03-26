<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Observer;

use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Extensions\ExtensionsFetcher;
use Magento\CommerceBackendUix\Model\RegistrationsFetcher;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Observer to load extensions on Admin Refresh
 */
class AdminRefreshObserver implements ObserverInterface
{
    /**
     * @param Cache $cache
     * @param AuthorizationValidator $authorization
     * @param ExtensionsFetcher $extensionsFetcher
     * @param RegistrationsFetcher $registrationsFetcher
     */
    public function __construct(
        private Cache $cache,
        private AuthorizationValidator $authorization,
        private ExtensionsFetcher $extensionsFetcher,
        private RegistrationsFetcher $registrationsFetcher
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        if ($this->authorization->isAuthorized()) {
            $extensions = $this->cache->getRegisteredExtensions();
            if (empty($extensions)) {
                $extensions = $this->extensionsFetcher->fetch();
                $this->cache->setRegisteredExtensions($extensions);
                $registrations = $this->registrationsFetcher->fetch();
                $this->cache->setRegistrations($registrations);
            }
        }
    }
}
