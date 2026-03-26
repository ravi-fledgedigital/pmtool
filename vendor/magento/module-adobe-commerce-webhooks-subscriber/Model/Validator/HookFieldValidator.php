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

use Magento\AdobeCommerceWebhooks\Api\Data\HookFieldInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates the hook field
 */
class HookFieldValidator implements HookDataValidatorInterface
{
    /**
     * Validates the hook fields
     *
     * @param HookInterface $hook
     * @return void
     * @throws ValidatorException
     */
    public function validate(HookInterface $hook): void
    {
        try {
            $fields = $hook->getHookData()[WebhookDataInterface::FIELDS] ?? [];
            if (!is_array($fields) || empty($fields)) {
                return;
            }

            foreach ($fields as $field) {
                if (empty($field[HookFieldInterface::NAME])) {
                    throw new ValidatorException(
                        __('The "name" for the hook field is required and can not be empty.')
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
