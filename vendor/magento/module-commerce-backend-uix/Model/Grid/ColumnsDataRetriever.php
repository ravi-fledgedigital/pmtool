<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model\Grid;

use Magento\CommerceBackendUix\Model\Config;
use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\Framework\HTTP\ClientInterface;

/**
 * Helper class to retrieve data for registered grid columns
 */
class ColumnsDataRetriever
{
    /**
     * @param ClientInterface $httpClient
     * @param ColumnsDataConverter $columnsDataConverter
     * @param LoggerHandler $logger
     * @param Config $config
     */
    public function __construct(
        private ClientInterface $httpClient,
        private ColumnsDataConverter $columnsDataConverter,
        private LoggerHandler $logger,
        private Config $config
    ) {
    }

    /**
     * Retrieve GraphQL data from external URL
     *
     * @param string $url
     * @param string $columnId
     * @param string $type
     * @param string $qry
     * @param string $entity
     * @param string $gridColumn
     * @return array
     */
    public function getColumnData(
        string $url,
        string $columnId,
        string $type,
        string $qry,
        string $entity,
        string $gridColumn
    ): array {
        $headers = [
            'Content-Type' => 'application/json',
            'x-ims-token' => $this->config->getIMSToken(),
            'x-gw-ims-org-id' => $this->config->getOrganizationId()
        ];

        $this->httpClient->setHeaders($headers);
        $this->httpClient->setOption(CURLOPT_POSTFIELDS, $qry);
        $this->httpClient->post($url, []);
        $response = $this->httpClient->getBody();
        if ($this->httpClient->getStatus() !== 200) {
            $errorMessage = 'Error retrieving data from API Mesh for ' . $columnId . ': ';
            $this->logger->debug($errorMessage . $this->httpClient->getStatus() . $response);
            return [];
        }
        return $this->columnsDataConverter->convertData($response, $columnId, $type, $entity, $gridColumn);
    }
}
