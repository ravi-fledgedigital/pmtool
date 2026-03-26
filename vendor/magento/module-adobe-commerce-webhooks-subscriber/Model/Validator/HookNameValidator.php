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
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates the hook name
 */
class HookNameValidator implements HookDataValidatorInterface
{
    public const MAX_HOOK_NAME_LENGTH = 128;

    /**
     * Validates that hook name contain only alphanumeric characters and underscores.
     *
     * @param HookInterface $hook
     * @return void
     * @throws ValidatorException
     */
    public function validate(HookInterface $hook): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $hook->getHookName())) {
            throw new ValidatorException(__(
                'The hook name can contain only alphanumeric characters and underscores.'
            ));
        }

        if (strlen($hook->getHookName()) > self::MAX_HOOK_NAME_LENGTH) {
            throw new ValidatorException(__(
                sprintf(
                    'The hook name length must be less than or equal to %d characters.',
                    self::MAX_HOOK_NAME_LENGTH
                )
            ));
        }
    }
}
