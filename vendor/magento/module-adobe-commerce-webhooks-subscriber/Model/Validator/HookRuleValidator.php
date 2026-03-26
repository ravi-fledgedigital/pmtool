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

use Magento\AdobeCommerceWebhooks\Api\Data\HookRuleInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;
use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorFactory;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates the hook rule
 */
class HookRuleValidator implements HookDataValidatorInterface
{
    /**
     * @param OperatorFactory $operatorFactory
     */
    public function __construct(private readonly OperatorFactory $operatorFactory)
    {
    }

    /**
     * Validates the hook rules
     *
     * @param HookInterface $hook
     * @return void
     * @throws ValidatorException
     */
    public function validate(HookInterface $hook): void
    {
        try {
            $rules = $hook->getHookData()[WebhookDataInterface::RULES] ?? [];
            if (!is_array($rules) || empty($rules)) {
                return;
            }

            foreach ($rules as $rule) {
                if (!is_array($rule)) {
                    continue;
                }

                $this->validateRule($rule);
            }
        } catch (WebhookConfigurationException $e) {
            throw new ValidatorException(__(
                'The webhook data has an invalid format. ' . $e->getMessage()
            ));
        }
    }

    /**
     * Validates a single rule
     *
     * @param array $rule
     * @return void
     * @throws ValidatorException
     */
    private function validateRule(array $rule): void
    {
        $requiredFields = [
            HookRuleInterface::OPERATOR,
            HookRuleInterface::FIELD,
        ];

        foreach ($requiredFields as $requiredField) {
            if (empty($rule[$requiredField])) {
                throw new ValidatorException(__('Rule field "%1" is required and can not be empty.', $requiredField));
            }
        }

        if (!isset($rule[HookRuleInterface::VALUE])) {
            throw new ValidatorException(__('Rule field "value" is required.'));
        }

        if (!in_array($rule[HookRuleInterface::OPERATOR], $this->operatorFactory->getOperatorsList())) {
            throw new ValidatorException(__(
                'Rule operator %1 is invalid. Allowed operators: [%2]',
                $rule[HookRuleInterface::OPERATOR],
                implode(', ', $this->operatorFactory->getOperatorsList())
            ));
        }
    }
}
