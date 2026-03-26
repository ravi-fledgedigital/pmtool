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

namespace Magento\AdobeCommerceWebhooks\Model\Ims;

use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\AdobeImsApi\Api\Data\TokenResponseInterfaceFactory;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Gets the token for the Developer Console Oauth
 */
class DeveloperConsoleOauth
{
    private const IMS_URL_PROD = 'https://ims-na1.adobelogin.com/ims/token/v3';
    private const IMS_URL_STAGE = 'https://ims-na1-stg1.adobelogin.com/ims/token/v3';

    /**
     * @param TokenResponseInterfaceFactory $tokenResponseFactory
     * @param CurlFactory $curlFactory
     * @param Json $json
     * @param TokenCacheHandler $tokenCacheHandler
     */
    public function __construct(
        private readonly TokenResponseInterfaceFactory $tokenResponseFactory,
        private readonly CurlFactory $curlFactory,
        private readonly Json $json,
        private readonly TokenCacheHandler $tokenCacheHandler
    ) {
    }

    /**
     * Returns request headers for the oauth authorization to Developer Console action
     *
     * @param CredentialsInterface $credentials
     * @return TokenResponseInterface
     * @throws AuthorizationException
     */
    public function getToken(CredentialsInterface $credentials): TokenResponseInterface
    {
        $tokenData = $this->tokenCacheHandler->getTokenData($credentials);
        if ($tokenData !== null) {
            return $this->tokenResponseFactory->create(['data' => $tokenData]);
        }

        $curl = $this->curlFactory->create();
        $curl->addHeader('cache-control', 'no-cache');
        $curl->post(
            $credentials->getEnvironment() === CredentialsInterface::ENVIRONMENT_STAGING ?
                self::IMS_URL_STAGE : self::IMS_URL_PROD,
            [
                'grant_type' => 'client_credentials',
                'client_id' => $credentials->getClientId(),
                'client_secret' => $credentials->getClientSecret(),
                'scope' => $credentials->getScopes()
            ]
        );

        $response = $this->json->unserialize($curl->getBody());

        if (!is_array($response) || empty($response['access_token'])) {
            $errorMessage = 'Could not login to Adobe IMS.';
            if (!empty($response['error'])) {
                $errorMessage .= sprintf(' Error: %s.', $response['error']);
            }
            throw new AuthorizationException(__($errorMessage));
        }

        $lifeTime = (int)($response['expires_in'] * 0.9);
        unset($response['expires_in']);
        $this->tokenCacheHandler->saveTokenData($credentials, $response, $lifeTime);

        return $this->tokenResponseFactory->create(['data' => $response]);
    }
}
