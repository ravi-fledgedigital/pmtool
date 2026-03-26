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

use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;

/**
 * Interface for hook field data from webapi requests
 *
 * @api
 */
interface HookFieldInterface
{
    public const NAME = HookField::NAME;
    public const SOURCE = HookField::SOURCE;

    /**
     * Sets field name.
     *
     * @param string $name
     * @return HookFieldInterface
     */
    public function setName(string $name): HookFieldInterface;

    /**
     * Returns field name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Sets field source.
     *
     * @param string $source
     * @return HookFieldInterface
     */
    public function setSource(string $source): HookFieldInterface;

    /**
     * Returns field source.
     *
     * @return string
     */
    public function getSource(): string;
}
