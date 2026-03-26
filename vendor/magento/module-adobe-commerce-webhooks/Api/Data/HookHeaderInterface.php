<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Api\Data;

use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader;

/**
 * Interface for hook header data from webapi requests
 *
 * @api
 */
interface HookHeaderInterface
{
    public const NAME = HookHeader::NAME;
    public const VALUE = HookHeader::VALUE;

    /**
     * Sets header name.
     *
     * @param string $name
     * @return HookHeaderInterface
     */
    public function setName(string $name): HookHeaderInterface;

    /**
     * Returns header name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Sets header value.
     *
     * @param string $value
     * @return HookHeaderInterface
     */
    public function setValue(string $value): HookHeaderInterface;

    /**
     * Returns header value.
     *
     * @return string
     */
    public function getValue(): string;
}
