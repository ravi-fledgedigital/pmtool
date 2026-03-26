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

use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates the hook type
 */
class HookTypeValidator implements HookDataValidatorInterface
{
    /**
     * Validates that webhook type is 'before' or 'after'
     *
     * @param HookInterface $hook
     * @return void
     * @throws ValidatorException
     */
    public function validate(HookInterface $hook): void
    {
        if (!in_array($hook->getWebhookType(), [Webhook::TYPE_BEFORE, Webhook::TYPE_AFTER])) {
            throw new ValidatorException(__(
                'The webhook type can be one of [%1, %2]',
                Webhook::TYPE_BEFORE,
                Webhook::TYPE_AFTER
            ));
        }
    }
}
