<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\Validator;

use Magento\AdobeCommerceWebhooks\Model\Webhook\AllowList\AllowedCheckerInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates whether a webhook method name is allowed.
 */
class WebhookAllowedValidator implements WebhookAllowedValidatorInterface
{
    /**
     * @param AllowedCheckerInterface $allowedChecker
     * @param string[] $disallowedWebhookExpressions
     */
    public function __construct(
        private AllowedCheckerInterface $allowedChecker,
        private array $disallowedWebhookExpressions = [],
    ) {
    }

    /**
     * Checks whether the webhook method name matches any of the disallowed webhook expressions.
     *
     * @param string $webhookMethodName
     * @return void
     * @throws ValidatorException
     */
    public function validate(string $webhookMethodName): void
    {
        $exception = function () use ($webhookMethodName) {
            throw new ValidatorException(__(
                'Creating a webhook with method name "%1" is not allowed.',
                $webhookMethodName
            ));
        };

        if (!$this->allowedChecker->isAllowed($webhookMethodName)) {
            $exception();
        }

        foreach ($this->disallowedWebhookExpressions as $disallowedWebhookExpression) {
            if (preg_match($disallowedWebhookExpression, $webhookMethodName)) {
                $exception();
            }
        }
    }
}
