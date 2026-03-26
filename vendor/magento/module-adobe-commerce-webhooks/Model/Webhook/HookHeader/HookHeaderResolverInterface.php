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

namespace Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\Framework\Exception\InvalidArgumentException;

/**
 * Returns a list of hook headers
 */
interface HookHeaderResolverInterface
{
    /**
     * Returns a list of hook headers
     *
     * @param Hook $hook
     * @param array $hookData
     * @return array
     * @throws InvalidArgumentException
     */
    public function resolve(Hook $hook, array $hookData): array;
}
