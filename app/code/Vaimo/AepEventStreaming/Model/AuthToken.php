<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model;

use Magento\Framework\App\CacheInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Api\RestClientInterface as RestClient;
use Vaimo\AepEventStreaming\Model\Request\GetToken as Request;
use Vaimo\AepEventStreaming\Model\Response\GetToken as Response;
use Vaimo\AepEventStreaming\Model\Response\GetTokenFactory as ResponseFactory;

class AuthToken
{
    public const CACHE_KEY = 'aep_event_streaming_auth_token';

    private CacheInterface $cache;
    private Request $request;
    private ResponseFactory $responseFactory;
    private RestClient $restClient;

    public function __construct(
        CacheInterface $cache,
        Request $request,
        ResponseFactory $responseFactory,
        RestClient $restClient
    ) {
        $this->cache = $cache;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->restClient = $restClient;
    }

    public function get(): string
    {
        $cachedToken = $this->cache->load(self::CACHE_KEY);

        if ($cachedToken) {
            return $cachedToken;
        }

        $aepToken = $this->getTokenFromAep();

        $this->cache->save(
            $aepToken->getAccessToken(),
            self::CACHE_KEY,
            [ConfigInterface::CACHE_TAG],
            $aepToken->getExpiresIn()
        );

        return $aepToken->getAccessToken();
    }

    public function flushCache(): void
    {
        $this->cache->remove(self::CACHE_KEY);
    }

    private function getTokenFromAep(): Response
    {
        $response = $this->restClient->sendRequest($this->request->buildRequest());

        return $this->responseFactory->create(['response' => $response]);
    }
}
