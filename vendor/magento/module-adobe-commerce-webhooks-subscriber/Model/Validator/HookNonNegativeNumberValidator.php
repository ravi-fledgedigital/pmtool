<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooksSubscriber\Model\Validator;

use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates the hook integer fields that should be not negative
 */
class HookNonNegativeNumberValidator implements HookDataValidatorInterface
{
    /**
     * Validates that the hook integer fields are not negative
     *
     * @param HookInterface $hook
     * @return void
     * @throws ValidatorException
     */
    public function validate(HookInterface $hook): void
    {
        try {
            $fields = [
                WebhookDataInterface::TIMEOUT,
                WebhookDataInterface::SOFT_TIMEOUT,
                WebhookDataInterface::TTL,
            ];

            $data = $hook->getHookData();
            foreach ($fields as $field) {
                if (!isset($data[$field])) {
                    continue;
                }
                if ($data[$field] < 0) {
                    throw new ValidatorException(
                        __('The field value "%1" must be equal to or greater than 0.', $field)
                    );
                }
            }
        } catch (WebhookConfigurationException $e) {
            throw new ValidatorException(__(
                'The webhook data has an invalid format. ' . $e->getMessage()
            ));
        }
    }
}
