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

namespace Magento\CommerceBackendUix\Model;

use Exception;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\CommerceBackendUix\Model\Parser\ParserInterface;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * RegistrationsFetcher class to fetch registrations of loaded extensions
 */
class RegistrationsFetcher
{
    private const REGISTRATION = 'registration';
    private const REGISTRATION_PATH = 'api/v1/web/admin-ui-sdk/registration';
    private const HTTP_STATUS_OK = 200;

    /**
     * @param Cache $cache
     * @param Config $config
     * @param ClientInterface $httpClient
     * @param Json $json
     * @param LoggerHandler $logger
     * @param ParserInterface[] $parsers
     */
    public function __construct(
        private Cache $cache,
        private Config $config,
        private ClientInterface $httpClient,
        private Json $json,
        private LoggerHandler $logger,
        private array $parsers
    ) {
    }

    /**
     * Fetch registrations of loaded extensions
     *
     * @return array
     */
    public function fetch(): array
    {
        $extensions = $this->cache->getRegisteredExtensions();
        $registrations = [];
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->config->getIMSToken(),
            'x-gw-ims-org-id' => $this->config->getOrganizationId()
        ];
        foreach ($extensions as $extensionId => $extensionUrl) {
            $registrationUrl = str_replace('index.html', self::REGISTRATION_PATH, $extensionUrl);
            try {
                $this->httpClient->setHeaders($headers);
                $this->httpClient->get($registrationUrl);
                $responseBody = $this->json->unserialize($this->httpClient->getBody());
                if ($this->httpClient->getStatus() === self::HTTP_STATUS_OK) {
                    $this->parseRegistrations($responseBody, $registrations, $extensionId);
                } else {
                    $this->logError($responseBody['error'] ?? 'Unknown error', $extensionId);
                }
            } catch (Exception $exception) {
                $this->logError($exception->getMessage(), $extensionId);
            }
        }
        return $registrations;
    }

    /**
     * Append and log error message
     *
     * @param string $errorMessage
     * @param string $extensionId
     * @return void
     */
    private function logError(string $errorMessage, string $extensionId): void
    {
        $formattedErrorMessage = sprintf(
            'Error while fetching registrations from App Registry for extension %s: %s',
            $extensionId,
            $errorMessage
        );
        $this->logger->error($formattedErrorMessage);
    }

    /**
     * Parse registration data
     *
     * @param array $responseBody
     * @param array $registrations
     * @param string $extensionId
     * @return void
     */
    private function parseRegistrations(array $responseBody, array &$registrations, string $extensionId): void
    {
        if (!isset($responseBody[self::REGISTRATION])) {
            return;
        }
        foreach ($this->parsers as $parser) {
            $parser->parse($responseBody[self::REGISTRATION], $registrations, $extensionId);
        }
    }
}
