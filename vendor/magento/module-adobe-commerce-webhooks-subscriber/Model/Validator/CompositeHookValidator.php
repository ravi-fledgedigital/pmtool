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

/**
 * Composite validator of provided hook
 */
class CompositeHookValidator implements HookDataValidatorInterface
{
    /**
     * @param HookDataValidatorInterface[] $validators
     */
    public function __construct(private array $validators)
    {
    }

    /**
     * @inheritDoc
     */
    public function validate(HookInterface $hook): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($hook);
        }
    }
}
