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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for @see \Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\RequestParams
 */
class RequestParamsFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private ObjectManagerInterface $objectManager,
    ) {
    }

    /**
     * Creates RequestParams class instance
     *
     * @param string $url
     * @param array $headers
     * @param array $body
     * @return RequestParams
     */
    public function create(string $url, array $headers, array $body): RequestParams
    {
        return $this->objectManager->create(
            RequestParams::class,
            [
                'data' => [
                    RequestParams::URL => $url,
                    RequestParams::HEADERS => $headers,
                    RequestParams::BODY => $body
                ]
            ]
        );
    }
}
