<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Plugin;

use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Http\Message\ResponseInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Api\Data\RequestInterface;
use Vaimo\AepEventStreaming\Api\RestClientInterface;

class RestClientPlugin
{
    private const UNKNOWN_ERROR_STATUS = 'unknown_error';

    private ConfigInterface $config;
    private EncryptorInterface $encryptor;

    /**
     * @var string[]|null
     */
    private ?array $serviceConfigs = null;

    public function __construct(
        ConfigInterface $config,
        EncryptorInterface $encryptor
    ) {
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    /**
     * @param RestClientInterface $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @param string[] $options
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function aroundSendRequest(
        RestClientInterface $subject,
        callable $proceed,
        RequestInterface $request,
        array $options = []
    ): ResponseInterface {

        $logKeepDays = null;
        $requestName = $request->getRequestName();

        $data = [];
        $logGroup = $request->getRequestGroup();
        $statusCode = self::UNKNOWN_ERROR_STATUS;

        try {
            $requestHeaders = $this->headersToString($request->getHeaders());
            $data['request'] = $this->sanitiseRequest((string) $request->getBody());
            $data['request_header'] = $requestHeaders;

            $startTime = microtime(true);

            /** @var ResponseInterface $response */
            $response = $proceed($request, $options);

            $data['response_time'] = \microtime(true) - $startTime;
            $statusCode = $response->getStatusCode();
            $data['response'] = $this->sanitizeResponse((string) $response->getBody());
            $responseHeaders = $this->headersToString($response->getHeaders());
            $data['response_header'] = $responseHeaders;
        } catch (\Throwable $e) {
            $data['error_message'] = $e->getMessage();

            throw $e;
        }

        return $response;
    }

    /**
     * @param string[][] $headers
     * @return string
     */
    private function headersToString(array $headers): string
    {
        $result = '';

        foreach ($headers as $headerKey => $headerValue) {
            if ($headerKey === 'Authorization') {
                $value = '*******';
            } else {
                $value = $headerValue;
            }

            if (\is_array($value)) {
                $value = \implode(' ', $value);
            }

            $result .= $headerKey . ': ' . $value . "\n";
        }

        return $result;
    }

    private function getServiceConfigValue(string $serviceClass, string $field): ?string
    {
        return $this->getServiceConfig()[$serviceClass][$field] ?? null;
    }

    /**
     * @return string[][]
     */
    private function getServiceConfig(): array
    {
        if ($this->serviceConfigs === null) {
            $this->populateConfig();
        }

        return $this->serviceConfigs;
    }

    private function populateConfig(): void
    {
        $this->serviceConfigs = [];

        $config = $this->helper->getServiceMethodsConfig();
        $classField = ServiceClassesConfig::CLASS_NAME_FIELD;
        $expiryField = ServiceClassesConfig::EXPIRY_DAYS_FIELD;

        foreach ($config as $configItem) {
            if (!isset($configItem[$classField])) {
                continue;
            }

            $this->serviceConfigs[$configItem[$classField]] = [
                $expiryField => $configItem[$expiryField] ?? null,
            ];
        }
    }

    private function sanitiseRequest(string $requestBody): string
    {
        return \str_replace(
            $this->encryptor->decrypt($this->config->getClientSecret()),
            '******',
            $requestBody
        );
    }

    private function sanitizeResponse(string $responseBody): string
    {
        $regex = '/("access_token":)\s*"[^"]+?([^\/"]+)"/';

        return preg_replace($regex, '${1}************', $responseBody);
    }
}