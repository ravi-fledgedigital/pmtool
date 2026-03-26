<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Request;

use Magento\Framework\Encryption\EncryptorInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Api\Data\RequestInterface as Request;
use Vaimo\AepEventStreaming\Api\Data\RequestInterfaceFactory as RequestFactory;
use Vaimo\AepEventStreaming\Api\RequestBuilderInterface;
use Vaimo\AepEventStreaming\Model\JWTToken;

class GetToken implements RequestBuilderInterface
{
    private const REQUEST_NAME = 'aep.getToken';
    private const HTTP_METHOD = 'POST';

    private ConfigInterface $config;
    private JWTToken $jwtToken;
    private EncryptorInterface $encryptor;
    private RequestFactory $requestFactory;

    public function __construct(
        ConfigInterface $config,
        JWTToken $jwtToken,
        EncryptorInterface $encryptor,
        RequestFactory $requestFactory
    ) {
        $this->config = $config;
        $this->jwtToken = $jwtToken;
        $this->encryptor = $encryptor;
        $this->requestFactory = $requestFactory;
    }

    public function buildRequest(): Request
    {
        return $this->requestFactory->create([
            'method' => $this->getMethod(),
            'uri' => $this->getUri(),
            'headers' => $this->getHeaders(),
            'requestName' => self::REQUEST_NAME,
            'requestGroup' => self::REQUEST_GROUP,
            'body' => \http_build_query($this->getBody(), '', '&'),
        ]);
    }

    /**
     * @return string[]
     */
    public function getBody(): array
    {
        return [
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->encryptor->decrypt($this->config->getClientSecret()),
            'jwt_token' => $this->jwtToken->get(),
        ];

        /*return [
            'grant_type' => $this->config->getGrantType(),
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->encryptor->decrypt($this->config->getClientSecret()),
            'scope' => $this->config->getClientScope()
        ];*/
    }

    /**
     * @return string[]
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Cache-Control' => 'no-cache',
        ];
    }

    private function getMethod(): string
    {
        return self::HTTP_METHOD;
    }

    private function getUri(): string
    {
        return $this->config->getAuthTokenEndpoint();
    }
}
