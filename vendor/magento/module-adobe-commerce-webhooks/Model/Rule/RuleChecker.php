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

namespace Magento\AdobeCommerceWebhooks\Model\Rule;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextPool;
use Magento\AdobeCommerceWebhooks\Model\Filter\ContextRetriever;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;

/**
 * Checks if webhooks data passed a list of rules.
 */
class RuleChecker implements RuleCheckerInterface
{
    /**
     * @param OperatorFactory $operatorFactory
     * @param ContextRetriever $contextRetriever
     * @param ContextPool $contextPool
     */
    public function __construct(
        private OperatorFactory $operatorFactory,
        private ContextRetriever $contextRetriever,
        private ContextPool $contextPool
    ) {
    }

    /**
     * @inheritDoc
     */
    public function verify(Hook $hook, array $webhookData): bool
    {
        foreach ($hook->getRules() as $rule) {
            if ($rule->shouldRemove()) {
                continue;
            }

            if (!$this->isContextRuleField($rule->getField())) {
                $data = $webhookData;
                foreach (explode('.', $rule->getField()) as $segment) {
                    if (!is_array($data) || !array_key_exists($segment, $data)) {
                        return false;
                    }
                    $data = $data[$segment];
                }
            } else {
                $data = $this->contextRetriever->getContextValue($rule->getField(), $hook);
            }

            $operator = $this->operatorFactory->create($rule->getOperator());
            if (!$operator->verify($data, $rule->getValue())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the rule field is a context field.
     *
     * @param string $source
     * @return bool
     */
    private function isContextRuleField(string $source): bool
    {
        $sourceParts = explode('.', $source);
        return $this->contextPool->has($sourceParts[0]);
    }
}
