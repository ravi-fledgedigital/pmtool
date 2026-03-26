<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductRecommendationsAdmin\Model;

use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesConnector\Exception\KeyNotFoundException;
use Magento\ServicesConnector\Api\KeyValidationInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class ServiceClient implements ServiceClientInterface
{
    /**
     * Config paths
     */
    const ROUTE_CONFIG_PATH = 'product_recommendations/admin_api_path';
    const ENVIRONMENT_CONFIG_PATH = 'magento_saas/environment';

    /**
     * Extension name for Services Connector
     */
    const EXTENSION_NAME = 'Magento_ProductRecommendationsAdmin';

    /**
     * @var ClientResolverInterface
     */
    private $clientResolver;

    /**
     * @var KeyValidationInterface
     */
    private $keyValidator;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ClientResolverInterface $clientResolver
     * @param KeyValidationInterface $keyValidator
     * @param ScopeConfigInterface $config
     * @param Json $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientResolverInterface $clientResolver,
        KeyValidationInterface $keyValidator,
        ScopeConfigInterface $config,
        Json $serializer,
        LoggerInterface $logger
    ) {
        $this->clientResolver = $clientResolver;
        $this->keyValidator = $keyValidator;
        $this->config = $config;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function request(string $method, string $uri, string $data = ''): array
    {
        $result = [];
        try {
            $client = $this->clientResolver->createHttpClient(
                self::EXTENSION_NAME,
                $this->config->getValue(self::ENVIRONMENT_CONFIG_PATH)
            );
            $headers = ['Content-Type' => 'application/json'];
            $options = [
                'headers' => $headers,
                'body' => $data
            ];

            if ($this->validateApiKey()) {
                $response = $client->request($method, $uri, $options);
                $result = $this->serializer->unserialize($response->getBody()->getContents());
            } else {
                $result = [
                    'status' => 403,
                    'statusText' => 'FORBIDDEN',
                    'message' => 'Magento API Key is invalid'
                ];
                $this->logger->error('API Key Validation Failed');
            }
        } catch (KeyNotFoundException $ex) {
            $result = [
                'status' => 403,
                'statusText' => 'FORBIDDEN',
                'message' => 'Magento API Key not found'
            ];
            $this->logger->error($ex->getMessage());
        } catch (GuzzleException | InvalidArgumentException $ex) {
            $result = [
                'status' => 500,
                'statusText' => 'INTERNAL_SERVER_ERROR',
                'message' => 'An error occurred'
            ];
            $this->logger->error(self::EXTENSION_NAME . ': ' . __('An error occurred contacting Magento Services'));
            $this->logger->error($ex->getMessage());
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getUrl(string $version, string $uri) : string
    {
        $route = $this->config->getValue(self::ROUTE_CONFIG_PATH);
        $url = sprintf('/%s/%s/%s', $route, $version, $uri);
        return $url;
    }

    /**
     * Validate the API Gateway Key
     *
     * @return bool
     * @throws KeyNotFoundException
     * @throws InvalidArgumentException
     */
    private function validateApiKey() : bool
    {
        return $this->keyValidator->execute(
            self::EXTENSION_NAME,
            $this->config->getValue(self::ENVIRONMENT_CONFIG_PATH)
        );
    }
}
