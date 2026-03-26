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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for operation classes for processing webhook response
 */
interface OperationInterface
{
    public const OPERATION_EXCEPTION = 'exception';
    public const OPERATION_SUCCESS = 'success';

    public const PATH = 'path';
    public const VALUE = 'value';
    public const INSTANCE = 'instance';

    /**
     * Processes webhook plugins arguments based on webhook response
     *
     * @param array $arguments
     * @return void
     * @throws OperationException when operation can't be performed
     * @throws LocalizedException
     */
    public function execute(array &$arguments): void;

    /**
     * Returns a Hook object for which this operation was created
     *
     * @return Hook
     */
    public function getHook(): Hook;
}
