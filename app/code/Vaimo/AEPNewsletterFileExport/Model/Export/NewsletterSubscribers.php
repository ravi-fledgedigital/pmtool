<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPNewsletterFileExport\Model\Export;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\Export\Factory as CollectionFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;
use Vaimo\AEPFileExport\Model\ExportEntityInterface;

class NewsletterSubscribers extends AbstractEntity implements ExportEntityInterface
{
    private const PAGE_SIZE = 500;
    public const LAST_RUN_FLAG_CODE = 'aep_newsletter_subscribers_export_last_run';

    private ?Collection $collection = null;

    private Mapping $mapping;
    private FlagManager $flagManager;
    private CollectionFactory $collectionFactory;
    private DateTime $dateTime;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $iteratorFactory
     * @param Mapping $mapping
     * @param FlagManager $flagManager
     * @param mixed[] $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        CollectionByPagesIteratorFactory $iteratorFactory,
        Mapping $mapping,
        FlagManager $flagManager,
        DateTime $dateTime,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $storeManager, $collectionFactory, $iteratorFactory, $data);
        $this->mapping = $mapping;
        $this->flagManager = $flagManager;
        $this->collectionFactory = $collectionFactory;
        $this->dateTime = $dateTime;
    }

    public function export(): void
    {
        $this->_byPagesIterator->iterate(
            $this->_getEntityCollection(),
            self::PAGE_SIZE,
            [[$this, 'exportItem']]
        );

        $this->flagManager->saveFlag(self::LAST_RUN_FLAG_CODE, $this->dateTime->date());
    }

    protected function _getEntityCollection(): Collection
    {
        if ($this->collection !== null) {
            return $this->collection;
        }

        $this->collection = $this->collectionFactory->create(Collection::class);
        $this->collection->addFieldToFilter('subscriber_email', ['neq' => 'NULL']);

        $this->applyMappingCallbacksToCollection();

        $lastRunDate = $this->flagManager->getFlagData(self::LAST_RUN_FLAG_CODE);
        if (empty($lastRunDate)) {
            return $this->collection;
        }

        $this->collection->addFieldToFilter(
            'change_status_at',
            ['gt' => $lastRunDate]
        );

        return $this->collection;
    }

    /**
     * @param Subscriber $item
     * @return void
     */
    public function exportItem($item): void
    {
        $result = [];
        $sourceItem = $item->getData();
        foreach ($this->mapping->getMapping() as $colName => $mapItem) {
            $result[$colName] = $sourceItem[$mapItem['attribute'] ?? null] ?? null;

            if (empty($mapItem['data_modification_callback'])) {
                continue;
            }

            $callbackName = $mapItem['data_modification_callback'];
            $result[$colName] = \method_exists($this->mapping, $callbackName)
                ? $this->mapping->$callbackName($item)
                : null;
        }

        $this->getWriter()->writeRow($result);
        $this->_processedRowsCount++;
    }

    public function getEntityTypeCode(): string
    {
        return 'newsletter_subscriber';
    }

    /**
     * @return string[]
     */
    protected function _getHeaderColumns(): array
    {
        return array_keys($this->mapping->getMapping());
    }

    private function applyMappingCallbacksToCollection()
    {
        foreach ($this->mapping->getMapping() as $item) {
            if (
                empty($item['prepare_collection_callback'])
                || !method_exists($this->mapping, $item['prepare_collection_callback'])
            ) {
                continue;
            }

            $callback = $item['prepare_collection_callback'];
            $this->mapping->$callback($this->collection);
        }
    }
}
