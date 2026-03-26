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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response;

use Magento\AdobeCommerceWebhooks\Model\Filter\Converter\HookFieldConverter;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;

/**
 * Handles conversion of operation values.
 */
class OperationValueConverter
{
    /**
     * @param HookFieldConverter $hookFieldConverter
     */
    public function __construct(
        private HookFieldConverter $hookFieldConverter
    ) {
    }

    /**
     * Converts a value if its path is equivalent to the source path of a HookField with a configured converter.
     *
     * @param mixed $value
     * @param string $valuePath
     * @param HookField[] $hookFields
     * @param array $webhookData
     * @return mixed
     */
    public function convert(mixed $value, string $valuePath, array $hookFields, array $webhookData): mixed
    {
        foreach ($hookFields as $hookField) {
            $fieldSource = $hookField->getSource() ?: $hookField->getName();
            if ($valuePath === str_replace('.', '/', $fieldSource)
                && $hookField->getConverter() !== null
            ) {
                return $this->hookFieldConverter->convertFromExternalFormat($value, $hookField, $webhookData);
            }
        }

        return $value;
    }
}
