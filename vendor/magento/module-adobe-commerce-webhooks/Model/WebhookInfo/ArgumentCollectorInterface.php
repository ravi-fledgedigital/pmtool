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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookInfo;

use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\Framework\Exception\LocalizedException;

/**
 * Collects argument data based on webhook
 */
interface ArgumentCollectorInterface
{
    /**
     * Collects argument data based on webhook name and type.
     *
     * @param Webhook $webhook
     * @return array
     * @throws LocalizedException
     */
    public function collect(Webhook $webhook): array;
}
