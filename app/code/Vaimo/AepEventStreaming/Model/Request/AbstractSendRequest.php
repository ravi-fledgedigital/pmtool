<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Request;

use Magento\Framework\Serialize\SerializerInterface;
use Vaimo\AepEventStreaming\Api\Data\RequestInterface as Request;
use Vaimo\AepEventStreaming\Api\Data\RequestInterfaceFactory as RequestFactory;
use Vaimo\AepEventStreaming\Api\RequestBuilderInterface;
use Vaimo\AepEventStreaming\Model\AuthToken;

abstract class AbstractSendRequest implements RequestBuilderInterface
{
    private const HTTP_METHOD = 'POST';

    private RequestFactory $requestFactory;
    private AuthToken $authToken;
    private SerializerInterface $serializer;

    public function __construct(
        RequestFactory $requestFactory,
        AuthToken $authToken,
        SerializerInterface $serializer
    ) {
        $this->requestFactory = $requestFactory;
        $this->authToken = $authToken;
        $this->serializer = $serializer;
    }

    /**
     * @return string[][]
     */
    abstract public function getBody(): array;

    abstract protected function getUri(): string;

    abstract protected function getRequestName(): string;

    public function buildRequest(): Request
    {
        return $this->requestFactory->create([
            'method' => $this->getMethod(),
            'uri' => $this->getUri(),
            'headers' => $this->getHeaders(),
            'requestName' => $this->getRequestName(),
            'requestGroup' => self::REQUEST_GROUP,
            'body' => $this->serializer->serialize($this->getBody()),
        ]);
    }

    /**
     * @return string[][]
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->authToken->get(),
        ];
    }

    private function getMethod(): string
    {
        return self::HTTP_METHOD;
    }
}
