<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Api\Data;

use Psr\Http\Message\RequestInterface as PsrRequestInterface;

/**
 * Additional functions useful for logging
 */
interface RequestInterface extends PsrRequestInterface
{
    public function getRequestName(): string;

    public function getRequestGroup(): string;
}
