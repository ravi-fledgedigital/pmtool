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

namespace Magento\AdobeCommerceWebhooksSubscriber\Model\Webapi;

use Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface;

/**
 * Obscures the developer console oauth configuration in the output data for rest API response
 */
class DeveloperConsoleOauthObscurer
{
    public const SECRET_PLACEHOLDER = '******';

    /**
     * Obscures the developer console oauth configuration in the output data for rest API response
     *
     * @param DeveloperConsoleOauthInterface $developerConsoleOauth
     * @param array $outputData
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(DeveloperConsoleOauthInterface $developerConsoleOauth, array $outputData): array
    {
        if (!empty($outputData[DeveloperConsoleOauthInterface::CLIENT_SECRET])) {
            $outputData[DeveloperConsoleOauthInterface::CLIENT_SECRET] = self::SECRET_PLACEHOLDER;
        }

        if (empty($outputData[DeveloperConsoleOauthInterface::ENVIRONMENT])) {
            unset($outputData[DeveloperConsoleOauthInterface::ENVIRONMENT]);
        }

        return $outputData;
    }
}
