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

use Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;
use Magento\AdobeCommerceWebhooks\Model\Ims\CredentialsInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates a hook's Developer Console OAuth settings
 */
class DeveloperConsoleOauthValidator implements HookDataValidatorInterface
{
    /**
     * Validates that Developer Console OAuth fields for the hook are set if OAuth is enabled.
     *
     * @param HookInterface $hook
     * @return void
     * @throws ValidatorException
     */
    public function validate(HookInterface $hook): void
    {
        $requiredOAuthFields = [
            DeveloperConsoleOauthInterface::CLIENT_ID => DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_ID,
            DeveloperConsoleOauthInterface::CLIENT_SECRET => DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_SECRET,
            DeveloperConsoleOauthInterface::ORG_ID => DeveloperConsoleOauthInterface::DC_OAUTH_ORG_ID,
            DeveloperConsoleOauthInterface::ENVIRONMENT => DeveloperConsoleOauthInterface::DC_OAUTH_ENVIRONMENT
        ];

        try {
            $hookData = $hook->getHookData();
            if (isset($hookData[DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED]) &&
                $hookData[DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED] === 'true'
            ) {
                $missingFields = array_filter($requiredOAuthFields, function ($field) use ($hookData) {
                    return empty($hookData[$field]);
                });

                if (!empty($missingFields)) {
                    throw new ValidatorException(__(
                        'Values for the following fields must be provided for Developer Console OAuth: %1',
                        implode(', ', array_keys($missingFields))
                    ));
                }

                if (!in_array(
                    $hookData[DeveloperConsoleOauthInterface::DC_OAUTH_ENVIRONMENT],
                    [CredentialsInterface::ENVIRONMENT_PRODUCTION, CredentialsInterface::ENVIRONMENT_STAGING]
                )) {
                    throw new ValidatorException(__(
                        'The Developer Console OAuth environment must be set to either "production" or "staging".'
                    ));
                }
            }
        } catch (WebhookConfigurationException $e) {
            throw new ValidatorException(__(
                'The webhook data has an invalid format. ' . $e->getMessage()
            ));
        }
    }
}
