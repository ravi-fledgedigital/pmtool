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

namespace Magento\AdobeCommerceWebhooks\Model\Config;

/**
 * Used for collecting webhook configuration from the specific source
 */
interface WebhookConfigurationSourceInterface
{
    /**
     * Returns an array of webhooks configuration where keys are used as webhook names
     *
     * @return array
     * @throws WebhookConfigurationException
     */
    public function getConfig(): array;
}
