<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\Ims;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Handles the caching of an IMS access tokens used for webhooks authorization.
 */
class TokenCacheHandler
{
    private const CACHE_ID = 'commerce-webhooks-ims-access-token';

    /**
     * @param CacheInterface $cache
     * @param EncryptorInterface $encryptor
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly EncryptorInterface $encryptor,
        private readonly Json $json,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Retrieves the IMS access token from the cache.
     *
     * @param CredentialsInterface $credentials
     * @return array|null
     */
    public function getTokenData(CredentialsInterface $credentials): ?array
    {
        $cachedTokenData = $this->cache->load($this->getCacheId($credentials));
        if ($cachedTokenData) {
            $tokenDataString = $this->encryptor->decrypt($cachedTokenData);
            try {
                $tokenData = $this->json->unserialize($tokenDataString);
                if (is_array($tokenData)) {
                    return $tokenData;
                }

                $this->logger->error(
                    'Cached access token data for the webhook has an unexpected type. Expected an array.'
                );
            } catch (\InvalidArgumentException $e) {
                $this->logger->error(
                    sprintf(
                        'Unable to deserialize cached access token data: %s',
                        $e->getMessage()
                    )
                );
            }
        }

        return null;
    }

    /**
     * Saves the IMS access token.
     *
     * @param CredentialsInterface $credentials
     * @param array $tokenData
     * @param int $lifeTime
     * @return void
     */
    public function saveTokenData(CredentialsInterface $credentials, array $tokenData, int $lifeTime): void
    {
        try {
            $tokenDataString = $this->json->serialize($tokenData);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error(
                sprintf(
                    'Unable to serialize access token data for caching: %s',
                    $e->getMessage()
                )
            );
            return;
        }

        $this->cache->save(
            $this->encryptor->encrypt($tokenDataString),
            $this->getCacheId($credentials),
            [],
            $lifeTime
        );
    }

    /**
     * Creates a cache ID for the IMS access token based on the provided credentials.
     *
     * @param CredentialsInterface $credentials
     * @return string
     */
    private function getCacheId(CredentialsInterface $credentials): string
    {
        return $this->encryptor->hash(implode('-', [
            self::CACHE_ID,
            $credentials->getClientId(),
            $credentials->getClientSecret(),
            $credentials->getOrgId(),
            $credentials->getScopes(),
            $credentials->getEnvironment()
        ]));
    }
}
