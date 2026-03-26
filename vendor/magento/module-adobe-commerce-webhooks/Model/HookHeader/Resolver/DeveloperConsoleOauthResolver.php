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

namespace Magento\AdobeCommerceWebhooks\Model\HookHeader\Resolver;

use Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\VariablesResolverInterface;
use Magento\AdobeCommerceWebhooks\Model\Ims\CredentialsInterface;
use Magento\AdobeCommerceWebhooks\Model\Ims\CredentialsInterfaceFactory;
use Magento\AdobeCommerceWebhooks\Model\Ims\DeveloperConsoleOauth;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\HookHeaderResolverInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AuthorizationException;
use Psr\Log\LoggerInterface;

/**
 * Resolves oauth headers for the request to Developer Console action if oauth is enabled.
 */
class DeveloperConsoleOauthResolver implements HookHeaderResolverInterface
{
    private const AUTHORIZATION_HEADER = 'Authorization';
    private const IMS_ORGANIZATION_HEADER = 'x-gw-ims-org-id';

    private const SCOPES = [
        'AdobeID',
        'openid',
        'read_organizations',
        'additional_info.projectedProductContext',
        'additional_info.roles',
        'adobeio_api',
        'read_client_secret',
        'manage_client_secrets'
    ];

    /**
     * @param DeveloperConsoleOauth $developerConsoleOauth
     * @param CredentialsInterfaceFactory $credentialsInterfaceFactory
     * @param LoggerInterface $logger
     * @param EncryptorInterface $encryptor
     * @param VariablesResolverInterface $variablesResolver
     * @param array $scopes
     */
    public function __construct(
        private readonly DeveloperConsoleOauth $developerConsoleOauth,
        private readonly CredentialsInterfaceFactory $credentialsInterfaceFactory,
        private readonly LoggerInterface $logger,
        private readonly EncryptorInterface $encryptor,
        private readonly VariablesResolverInterface $variablesResolver,
        private readonly array $scopes = self::SCOPES
    ) {
    }

    /**
     * Returns request headers for the oauth authorization to Developer Console action
     *
     * @param Hook $hook
     * @param array $hookData
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Hook $hook, array $hookData): array
    {
        $isEnabled = $hook->getData(DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED);
        if ($isEnabled !== 'true' && $isEnabled !== true) {
            return [];
        }

        try {
            $credentials = $this->getCredentials($hook);
            $token = $this->developerConsoleOauth->getToken($credentials);

            return [
                self::AUTHORIZATION_HEADER => 'Bearer ' . $token->getAccessToken(),
                self::IMS_ORGANIZATION_HEADER => $credentials->getOrgId()
            ];
        } catch (AuthorizationException $e) {
            $this->logger->error(
                'Could not generate Developer Console authorization token: ' . $e->getMessage(),
                [
                    'hook' => $hook,
                    'destination' => ['internal', 'external'],
                ]
            );
        }

        return [];
    }

    /**
     * Creates a credentials object based on Hook data.
     *
     * Check if the client secret is encrypted and decrypts it if necessary.
     * Check if any of the credential values contain variables patterns and resolves them.
     *
     * @param Hook $hook
     * @return CredentialsInterface
     */
    private function getCredentials(Hook $hook): CredentialsInterface
    {
        $clientSecret = $hook->getData(DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_SECRET);
        $data = [
            CredentialsInterface::CLIENT_ID => $hook->getData(DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_ID),
            CredentialsInterface::CLIENT_SECRET => is_string($clientSecret) && preg_match('/^\d+:\d+:/', $clientSecret)
                ? $this->encryptor->decrypt($clientSecret)
                : $clientSecret,
            CredentialsInterface::ORG_ID => $hook->getData(DeveloperConsoleOauthInterface::DC_OAUTH_ORG_ID),
        ];

        foreach ($data as $key => $value) {
            $data[$key] = $this->variablesResolver->resolve((string)$value);
        }

        $data[CredentialsInterface::SCOPES] = implode(',', $this->scopes);
        $data[CredentialsInterface::ENVIRONMENT] =
            (string)$hook->getData(DeveloperConsoleOauthInterface::DC_OAUTH_ENVIRONMENT);

        return $this->credentialsInterfaceFactory->create(['data' => $data]);
    }
}
