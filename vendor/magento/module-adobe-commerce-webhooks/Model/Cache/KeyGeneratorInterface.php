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

namespace Magento\AdobeCommerceWebhooks\Model\Cache;

use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\RequestParams;

/**
 * Generates keys for caching hook responses.
 */
interface KeyGeneratorInterface
{
    /**
     * Generates a key for caching a hook response given the hook request parameters.
     *
     * @param RequestParams $requestParams
     * @return string
     */
    public function generate(RequestParams $requestParams): string;
}
