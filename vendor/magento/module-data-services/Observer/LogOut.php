<?php
/**
 * Copyright 2019 Adobe
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

namespace Magento\DataServices\Observer;

use Magento\Cookie\Helper\Cookie as CookieHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * LogOut observer for data services events
 */
class LogOut implements ObserverInterface
{
    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param ConfigInterface $sessionConfig
     * @param Json $jsonSerializer
     * @param CookieHelper $cookieHelper
     */
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly ConfigInterface  $sessionConfig,
        private readonly Json $jsonSerializer,
        private readonly CookieHelper $cookieHelper
    ) { }

    /**
     * Set a flag if customers logs in or out
     *
     * @param Observer $observer
     * @return void
     * @throws FailureToSendException If cookie couldn't be sent to the browser.
     * @throws CookieSizeLimitReachedException Thrown when the cookie is too big to store any additional data.
     * @throws InputException If the cookie name is empty or contains invalid characters.
     * @throws NoSuchEntityException If store entity cannot be found
     */
    public function execute(Observer $observer)
    {
        if ($this->cookieHelper->isUserNotAllowSaveCookie()) {
            return;
        }

        /** @var PublicCookieMetadata $publicCookieMetadata */
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration($this->sessionConfig->getCookieLifetime())
            ->setPath('/')
            ->setDomain($this->sessionConfig->getCookieDomain())
            ->setHttpOnly(false);

        $this->cookieManager->setPublicCookie(
            "authentication_flag",
            $this->jsonSerializer->serialize(true),
            $publicCookieMetadata
        );

        $this->cookieManager->deleteCookie("dataservices_customer_id", $publicCookieMetadata);
        $this->cookieManager->deleteCookie("dataservices_customer_group", $publicCookieMetadata);
        $this->cookieManager->deleteCookie("dataservices_cart_id", $publicCookieMetadata);
    }
}
