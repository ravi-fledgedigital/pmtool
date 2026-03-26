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
 * ExtensionManagerFetcher class to fetch extensions from Extension Manager
 */
class ExtensionManagerFetcher implements ExtensionsFetcherInterface
{
    private const HEADER_X_ORG_ID = 'x-org-id';

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
        $this->httpClient->setHeaders(
            [
                self::HEADER_ACCEPT => self::HEADER_VALUE_APPLICATION_JSON,
                self::HEADER_AUTHORIZATION => 'Bearer ' . $this->config->getIMSToken(),
                self::HEADER_X_ORG_ID => $this->config->getOrganizationId(),
                self::HEADER_X_API_KEY => 'aemx-mngr-adobe-commerce'
            ]
        );
        $this->httpClient->setTimeout(self::TIMEOUT_IN_SECONDS);
        $this->setCurlOptions();

        $extensions = [];
        try {
            $this->httpClient->get($this->config->getExtensionManagerUrl());
            $extensions = $this->deserializeExtensionManagerExtensions();
        } catch (Exception $exception) {
            $this->logger->error(
                'Error while fetching extensions registrations from Extension Manager: '
                . $exception->getMessage()
            );
        }
        return $extensions;
    }

    /**
     * Returns deserialized Extension Manager extensions
     *
     * @return array
     * @throws RemoteServiceUnavailableException
     */
    private function deserializeExtensionManagerExtensions(): array
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
            if ($extension['name'] && $extension['status'] === self::STATUS_PUBLISHED) {
                $commerceExtensionPoint = array_values(
                    array_filter($extension['extensionPoints'], function ($extensionPoint) {
                        return $extensionPoint['extensionPoint'] === 'commerce/backend-ui/1';
                    })
                );
                if (!empty($commerceExtensionPoint)) {
                    $extensions[$extension['name']] = $commerceExtensionPoint[0]['url'];
                }
            }
        }
        return $extensions;
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
