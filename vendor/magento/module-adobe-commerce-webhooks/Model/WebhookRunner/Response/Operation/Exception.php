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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Exception operation to interrupt the process
 */
class Exception implements OperationInterface
{
    /**
     * @param Hook $hook
     * @param array $configuration
     */
    public function __construct(
        private Hook $hook,
        private array $configuration,
    ) {
    }

    /**
     * Throws the exception declared in the configuration if exception type is not provided throws LocalizedException
     *
     * @param array $arguments
     * @return void
     * @throws LocalizedException
     */
    public function execute(array &$arguments): void
    {
        $exceptionClass = $this->configuration['type'] ?? LocalizedException::class;
        throw new $exceptionClass(__($this->getExceptionMessage()));
    }

    /**
     * @inheritDoc
     */
    public function getHook(): Hook
    {
        return $this->hook;
    }

    /**
     * Returns error message for the Exception operation.
     *
     * If the message from the configuration is empty, returns the hook fallback error message.
     *
     * @return string
     */
    private function getExceptionMessage(): string
    {
        if (!empty($this->configuration['message'])) {
            return $this->configuration['message'];
        }

        return $this->getHook()->getFallbackErrorMessage();
    }
}
