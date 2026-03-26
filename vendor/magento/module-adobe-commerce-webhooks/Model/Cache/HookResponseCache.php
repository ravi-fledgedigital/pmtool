<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\Cache;

use Magento\AdobeCommerceWebhooks\Model\Cache\Type\WebhookResponse;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\RequestParams;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles caching of hook responses.
 */
class HookResponseCache
{
    public const CACHED_STATE = 'cached';

    /**
     * Temporary cache for storing responses during the same request.
     *
     * @var array
     */
    private array $temporaryCache = [];

    /**
     * @param CacheInterface $cache
     * @param KeyGeneratorInterface $keyGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheInterface $cache,
        private KeyGeneratorInterface $keyGenerator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Saves a hook response to the cache with a key created using the request's parameters.
     *
     * @param RequestParams $requestParams
     * @param string $hookResponse
     * @param Hook $hook
     * @return void
     */
    public function saveResponse(RequestParams $requestParams, string $hookResponse, Hook $hook): void
    {
        $ttl = $hook->getTtl();
        $cacheKey = $this->getCacheKey($requestParams);

        $this->temporaryCache[$cacheKey] = $hookResponse;

        if ($ttl !== 0) {
            $this->cache->save(
                $hookResponse,
                $cacheKey,
                [WebhookResponse::CACHE_TAG],
                $ttl
            );

            $this->logger->debug(
                sprintf(
                    'Request to url %s for hook "%s" has been saved to cache with ttl %s.',
                    $hook->getUrl(),
                    $hook->getName(),
                    $ttl
                ),
                ['hook' => $hook]
            );
        }
    }

    /**
     * Retrieves a hook response from the cache given a set of hook request parameters.
     *
     * @param RequestParams $requestParams
     * @param Hook $hook
     * @return string|null
     */
    public function getResponse(RequestParams $requestParams, Hook $hook): ?string
    {
        $cacheKey = $this->getCacheKey($requestParams);
        if (isset($this->temporaryCache[$cacheKey])) {
            return $this->temporaryCache[$cacheKey];
        }

        $response = $this->cache->load($cacheKey);
        if ($response) {
            $this->logger->debug(
                sprintf(
                    'Request to url %s for hook "%s" has been loaded from cache.',
                    $hook->getUrl(),
                    $hook->getName()
                ),
                ['hook' => $hook]
            );
        }
        return $response ?: null;
    }

    /**
     * Generates a cache key for a hook response.
     *
     * @param RequestParams $requestParams
     * @return string
     */
    private function getCacheKey(RequestParams $requestParams): string
    {
        return WebhookResponse::TYPE_IDENTIFIER . '_' . $this->keyGenerator->generate($requestParams);
    }
}
