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

namespace Magento\AdobeCommerceWebhooks\Model\Filter;

use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;

/**
 * Used for producing a filtered data payload
 */
interface FieldProcessorInterface
{
    /**
     * Filters the input data payload given a list of HookFields
     *
     * @param array $data
     * @param HookField[] $hookFields
     * @return array
     */
    public function process(array $data, array $hookFields): array;
}
