<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Cookie;

use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class HashChecker
{
    public const HASH_KEY = 'amrma_guest_hash';

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath('/')
            ->setDurationOneYear();

        $this->cookieManager->setPublicCookie(
            self::HASH_KEY,
            $hash,
            $cookieMetadata
        );
    }

    public function getHash()
    {
        return $this->cookieManager->getCookie(self::HASH_KEY);
    }

    public function removeHash()
    {
        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath('/');

        $this->cookieManager->deleteCookie(
            self::HASH_KEY,
            $cookieMetadata
        );
    }
}
