<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductRecommendationsAdmin\Model;

/**
 * Interface for SaaS service calls for product recommendations
 *
 * @api
 */
interface ServiceClientInterface
{
    /**
     * Execute call to SaaS service
     *
     * @param string $method
     * @param string $uri
     * @param string $data
     * @return array
     */
    public function request(string $method, string $uri, string $data = ''): array;

    /**
     * Build URL to SaaS Service
     *
     * @param string $version
     * @param string $uri
     * @return string
     */
    public function getUrl(string $version, string $uri) : string;
}