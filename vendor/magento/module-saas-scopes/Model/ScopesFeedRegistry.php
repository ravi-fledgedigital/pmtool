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

namespace Magento\SaaSScopes\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\SaaSCommon\Model\FeedRegistry;

/**
 * Filter Adapter to build the expected scopes JSON payload in the external feed ingestion service.
 */
class ScopesFeedRegistry extends FeedRegistry
{
    /**
     * @var string wrapper element for feed_data data.
     */
    private string $feedDataWrapper;

    /**
     * @param string $feedDataWrapper element to wrap the feed_data info from the data_exporter table
     * @param ResourceConnection $resourceConnection
     * @param SerializerInterface $serializer
     * @param string $registryTable
     * @param array $uniqueFields
     * @param array $excludeFields
     */
    public function __construct(
        string $feedDataWrapper,
        ResourceConnection $resourceConnection,
        SerializerInterface $serializer,
        string $registryTable = '',
        array $uniqueFields = [],
        array $excludeFields = []
    ) {
        parent::__construct(
            $resourceConnection,
            $serializer,
            $registryTable,
            $uniqueFields,
            $excludeFields
        );
        $this->feedDataWrapper = $feedDataWrapper;
    }

    /**
     * @inheritDoc
     */
    public function filter(array $data): array
    {
        return parent::filter($this->wrap($data));
    }

    /**
     * Wraps the feed_data attribute inside the $feedDataWrapper element as a key to replace the current feed_data value.
     * @param array $data
     * @return array
     */
    private function wrap(array $data): array
    {
        $wrappedData = [];
        foreach ($data as $record) {
            $updatedAt = new \DateTime($record['modifiedAt'] ?? 'now');
            $delete = $record['deleted'] ?? false;
            unset($record['modifiedAt'], $record['deleted']);
            $wrappedData[] = [
                $this->feedDataWrapper => $record,
                'updatedAt' => $updatedAt->format(\DateTime::ATOM),
                'deleted' => $delete
            ];
        }
        return $wrappedData;
    }
}
