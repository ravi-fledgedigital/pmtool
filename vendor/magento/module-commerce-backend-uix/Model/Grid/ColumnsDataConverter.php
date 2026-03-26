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

namespace Magento\CommerceBackendUix\Model\Grid;

use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Helper class containing data conversion logic for JSON data retrieved
 * from external source to populate registered grid columns
 */
class ColumnsDataConverter
{
    private const EMPTY_VALUE = '';

    /**
     * @param LoggerHandler $logger
     * @param Json $json
     * @param ColumnsDataFormatter $columnDataFormatter
     */
    public function __construct(
        private LoggerHandler $logger,
        private Json $json,
        private ColumnsDataFormatter $columnDataFormatter
    ) {
    }

    /**
     * Convert the received JSON data
     *
     * @param string $dataSource
     * @param string $columnId
     * @param string $type
     * @param string $entity
     * @param string $gridColumn
     * @return array
     */
    public function convertData(
        string $dataSource,
        string $columnId,
        string $type,
        string $entity,
        string $gridColumn
    ): array {
        $dataSource = $this->json->unserialize($dataSource);
        $invalidDataIds = [];

        if (!isset($dataSource['data'][$entity][$gridColumn])) {
            $this->logger->debug('Error processing data for ' . $columnId . ': Unexpected JSON format');
            return [];
        }
        foreach ($dataSource['data'][$entity][$gridColumn] as $id => &$config) {
            if (!is_array($config) || !array_key_exists($columnId, $config)) {
                $invalidDataIds[] = $id;
                continue;
            }

            try {
                $config[$columnId] = $this->columnDataFormatter->format($config[$columnId], $type);
            } catch (ValidatorException $exception) {
                $invalidDataIds[] = $id;
                $config[$columnId] = self::EMPTY_VALUE;
            }
        }
        if (!empty($invalidDataIds)) {
            $errorMessage = sprintf('Error validating column grid %s for %s: ', $columnId, $entity);
            $this->logger->debug($errorMessage . implode(", ", $invalidDataIds));
        }
        return $dataSource;
    }
}
