<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Api;

interface RequestBuilderInterface
{
    public const REQUEST_GROUP = 'aep';

    public function buildRequest(): Data\RequestInterface;

    /**
     * @return string[][]
     */
    public function getBody(): array;
}
