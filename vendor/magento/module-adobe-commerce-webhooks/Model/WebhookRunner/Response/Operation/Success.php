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

/**
 * Success operation
 */
class Success implements OperationInterface
{
    /**
     * @param Hook $hook
     */
    public function __construct(private Hook $hook)
    {
    }

    /**
     * Currently this operation do nothing it means that code execution proceed without any changes
     *
     * @param array $arguments
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock
     */
    public function execute(array &$arguments): void
    {
    }

    /**
     * @inheritDoc
     */
    public function getHook(): Hook
    {
        return $this->hook;
    }
}
