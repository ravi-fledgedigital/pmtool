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

use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\AdobeCommerceWebhooks\Model\Validator\WebhookAllowedValidatorInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates if the webhook method is allowed
 */
class HookAllowedMethodValidator implements HookDataValidatorInterface
{
    /**
     * @param WebhookAllowedValidatorInterface $webhookAllowedValidator
     */
    public function __construct(private WebhookAllowedValidatorInterface $webhookAllowedValidator)
    {
    }

    /**
     * Validates if the webhook method is allowed
     *
     * @param HookInterface $hook
     * @return void
     * @throws ValidatorException
     */
    public function validate(HookInterface $hook): void
    {
        $this->webhookAllowedValidator->validate($hook->getWebhookMethod());
    }
}
