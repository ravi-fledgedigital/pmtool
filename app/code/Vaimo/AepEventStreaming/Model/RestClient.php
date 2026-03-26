<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Vaimo\AepEventStreaming\Api\Data\RequestInterface;
use Vaimo\AepEventStreaming\Api\RestClientInterface;

class RestClient implements RestClientInterface
{
    private HttpClient $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param RequestInterface $request
     * @param string[] $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function sendRequest(RequestInterface $request, array $options = []): ResponseInterface
    {
        $options[RequestOptions::HTTP_ERRORS] = false;

        return $this->client->send($request, $options);
    }
}
