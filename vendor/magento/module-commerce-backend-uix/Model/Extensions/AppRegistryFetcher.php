<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model\Extensions;

use Exception;
use Magento\CommerceBackendUix\Model\Config;
use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\Framework\Exception\RemoteServiceUnavailableException;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * AppRegistryFetcher class to fetch extensions from App Registry
 */
class AppRegistryFetcher implements ExtensionsFetcherInterface
{
    /**
     * @param Config $config
     * @param ClientInterface $httpClient
     * @param LoggerHandler $logger
     * @param Json $json
     */
    public function __construct(
        private Config $config,
        private ClientInterface $httpClient,
        private LoggerHandler $logger,
        private Json $json
    ) {
    }

    /**
     * @inheritdoc
     */
    public function fetch(): array
    {
        $appRegistryUrl = $this->config->getRegistryBaseURL()
            . '/myxchng/v1/org/'
            . $this->config->getOrganizationId()
            . '/xtn/commerce/backend-ui/1?auth=true';

        $this->httpClient->setHeaders(
            [
                self::HEADER_ACCEPT => self::HEADER_VALUE_APPLICATION_JSON,
                self::HEADER_AUTHORIZATION => 'Bearer ' . $this->config->getIMSToken(),
                self::HEADER_X_API_KEY => 'exc_app'
            ]
        );
        $this->httpClient->setTimeout(self::TIMEOUT_IN_SECONDS);
        $this->setCurlOptions();

        $extensions = [];
        try {
            $this->httpClient->get($appRegistryUrl);
            $extensions = $this->deserializeAppRegistryExtensions();
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf(
                    'Error while fetching extensions registrations from App Registry: %s',
                    $exception->getMessage()
                )
            );
        }
        return $extensions;
    }

    /**
     * Returns deserialized App Registry extensions
     *
     * @return array
     * @throws RemoteServiceUnavailableException
     */
    private function deserializeAppRegistryExtensions(): array
    {
        if ($this->httpClient->getStatus() !== 200) {
            $errorMessage = sprintf(
                '%s, Status code: %d',
                $this->httpClient->getBody(),
                $this->httpClient->getStatus()
            );
            throw new RemoteServiceUnavailableException(__($errorMessage));
        }
        $extensions = [];
        foreach ($this->json->unserialize($this->httpClient->getBody()) as $extension) {
            if ($extension['name'] && $this->isStatusEligible($extension['status'])) {
                $extensions[$extension['name']] =
                    $extension['endpoints']['commerce/backend-ui/1']['view'][0]['href'] ?? '';
            }
        }
        return $extensions;
    }

    /**
     * Checks if the extension status is eligible
     *
     * @param string $status
     * @return bool
     */
    private function isStatusEligible(string $status): bool
    {
        if ($this->config->isSandboxTestingEnabled()) {
            $selectedAppStatus = array_map('strtolower', $this->config->getSelectedAppStatus());
            return in_array(strtolower($status), $selectedAppStatus, true);
        }
        return $status === self::STATUS_PUBLISHED;
    }

    /**
     * Sets Curl options for local testing
     *
     * @return void
     */
    private function setCurlOptions(): void
    {
        if ($this->config->isLocalTestingEnabled()) {
            $this->httpClient->setOptions(
                [
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0
                ]
            );
        }
    }
}
