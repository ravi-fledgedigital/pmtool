<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Api;

use Psr\Http\Message\ResponseInterface;

interface RestClientInterface
{
    public const HTTP_CODE_OK = 200;
    public const HTTP_CODE_UNAUTHORISED = 401;

    /**
     * @param Data\RequestInterface $request
     * @param string[] $options
     * @return ResponseInterface
     */
    public function sendRequest(Data\RequestInterface $request, array $options = []): ResponseInterface;
}
