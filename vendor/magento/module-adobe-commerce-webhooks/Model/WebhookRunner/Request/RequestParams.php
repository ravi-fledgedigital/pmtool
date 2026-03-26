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

use Magento\Framework\DataObject;

/**
 * Data object for storing resolved hook request parameters.
 */
class RequestParams extends DataObject
{
    public const URL = 'url';
    public const HEADERS = 'headers';
    public const BODY = 'body';

    /**
     * Returns resolved hook url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return (string)$this->getData(self::URL);
    }

    /**
     * Returns resolved hook request headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->getData(self::HEADERS);
    }

    /**
     * Returns the hook request body.
     *
     * @return array
     */
    public function getBody(): array
    {
        return $this->getData(self::BODY);
    }
}
