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

namespace Magento\AdobeCommerceWebhooks\Model\Filter\Converter;

use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;

/**
 * Used for converting values before sending requests to a hook url and after receiving responses.
 *
 * @api
 */
interface FieldConverterInterface
{
    /**
     * Converts a value before sending a request to a hook url.
     *
     * @param mixed $value
     * @param HookField $field
     * @param array $pluginData
     * @return mixed
     */
    public function toExternalFormat(mixed $value, HookField $field, array $pluginData);

    /**
     * Converts a value received in a response from a hook url.
     *
     * @param mixed $value
     * @param HookField $field
     * @param array $pluginData
     * @return mixed
     */
    public function fromExternalFormat(mixed $value, HookField $field, array $pluginData);
}
