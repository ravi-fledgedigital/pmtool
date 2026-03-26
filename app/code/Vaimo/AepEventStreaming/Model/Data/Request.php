<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Data;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Vaimo\AepEventStreaming\Api\Data\RequestInterface;

class Request extends GuzzleRequest implements RequestInterface
{
    private string $requestName;
    private string $requestGroup;

    /**
     * @param string                               $method  HTTP method
     * @param string|UriInterface                  $uri     URI
     * @param string[][]                           $headers Request headers
     * @param string|resource|StreamInterface|null $body    Request body
     * @param string                               $version Protocol version
     */
    public function __construct(
        $method,
        $uri,
        string $requestName,
        string $requestGroup,
        array $headers = [],
        $body = null,
        $version = '1.1'
    ) {
        parent::__construct($method, $uri, $headers, $body, $version);
        $this->requestName = $requestName;
        $this->requestGroup = $requestGroup;
    }

    public function getRequestName(): string
    {
        return $this->requestName;
    }

    public function getRequestGroup(): string
    {
        return $this->requestGroup;
    }
}
