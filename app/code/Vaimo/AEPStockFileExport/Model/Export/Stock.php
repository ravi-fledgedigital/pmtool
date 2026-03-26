<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPStockFileExport\Model\Export;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Inventory\Model\ResourceModel\SourceItem\Collection as SourceItemCollection;
use Magento\InventoryImportExport\Model\Export\AttributeCollectionProvider;
use Magento\InventoryImportExport\Model\Export\ColumnProviderInterface;
use Magento\InventoryImportExport\Model\Export\SourceItemCollectionFactoryInterface;
use Magento\InventoryImportExport\Model\Export\Sources;
use Magento\Store\Model\StoreManagerInterface;
use Vaimo\AEPFileExport\Model\ExportEntityInterface;
use Vaimo\AEPStockFileExport\Model\ResourceModel\GetSourcesByWebsites;

class Stock extends Sources implements ExportEntityInterface
{
    private const MODIFIED_DATE_FORMAT = 'Y-m-d\TH:i:s.Z\Z';

    private const BATCH_SIZE = 500;

    /**
     * @var string[]|null
     */
    private ?array $sourcesMap = null;
    private ?string $modifiedDate = null;
    private SourceItemCollectionFactoryInterface $sourceItemCollectionFactory;
    private DateTime $dateTime;
    private GetSourcesByWebsites $getSourcesByWebsites;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ExportFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param AttributeCollectionProvider $attributeCollectionProvider
     * @param SourceItemCollectionFactoryInterface $sourceItemCollectionFactory
     * @param ColumnProviderInterface $columnProvider
     * @param DateTime $dateTime
     * @param GetSourcesByWebsites $getSourcesByWebsites
     * @param mixed[] $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        AttributeCollectionProvider $attributeCollectionProvider,
        SourceItemCollectionFactoryInterface $sourceItemCollectionFactory,
        ColumnProviderInterface $columnProvider,
        DateTime $dateTime,
        GetSourcesByWebsites $getSourcesByWebsites,
        array $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $attributeCollectionProvider,
            $sourceItemCollectionFactory,
            $columnProvider,
            $data
        );
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->dateTime = $dateTime;
        $this->getSourcesByWebsites = $getSourcesByWebsites;
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    protected function _getHeaderColumns(): array
    {
        return [
            'skuStoreViewCode',
            'Stock',
            'Modified_Date',
        ];
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function export(): string
    {
        $writer = $this->getWriter();
        $writer->setHeaderCols($this->_getHeaderColumns());

        $page = 1;
        while (true) {
            /** @var SourceItemCollection $collection */
            $collection = $this->sourceItemCollectionFactory->create(
                $this->getAttributeCollection(),
                $this->_parameters
            )->setPageSize(self::BATCH_SIZE)
                ->setCurPage($page++);

            $items = $this->groupBySku($collection->load()->getData());
            foreach ($this->prepareData($items) as $data) {
                $writer->writeRow($data);
                $this->_processedRowsCount++;
            }

            if ($page > $collection->getLastPageNumber()) {
                break;
            }
        }

        return $writer->getContents();
    }

    /**
     * @param string[] $rawData
     * @return string[][][]
     */
    private function groupBySku(array $rawData): array
    {
        $data = [];
        foreach ($rawData as $item) {
            $data[$item['sku']][$item['source_code']] = $item;
        }

        return $data;
    }

    /**
     * @param string[][][] $data
     * @return string[][]
     */
    private function prepareData(array $data): array
    {
        $result = [];
        $storeSourceMap = $this->getSourceMapping();
        foreach ($data as $sources) {
            foreach ($this->_storeManager->getStores() as $store) {
                if (empty($sources[$storeSourceMap[$store->getCode()]])) {
                    continue;
                }

                $item = $sources[$storeSourceMap[$store->getCode()]];
                $item['storeCode'] = $store->getCode();
                $result[] = $this->modifyRow($item);
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getSourceMapping(): array
    {
        if ($this->sourcesMap !== null) {
            return $this->sourcesMap;
        }

        foreach ($this->_storeManager->getStores() as $store) {
            $websiteMap = $this->getSourcesByWebsites->execute();
            $websiteCode = $store->getWebsite()->getCode();
            // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
            if (!empty($websiteMap[$websiteCode])) {
                $this->sourcesMap[$store->getCode()] = $websiteMap[$websiteCode]['source'];
            }
        }

        return $this->sourcesMap;
    }

    /**
     * @param string[] $data
     * @return string[]
     */
    private function modifyRow(array $data): array
    {
        return [
            'skuStoreViewCode' => $data['sku'] . '|' . $data['storeCode'],
            'Stock' => (bool) $data['status'] ? $data['quantity'] : '0',
            'Modified_Date' => $this->getModifiedDate(),
        ];
    }

    private function getModifiedDate(): string
    {
        if ($this->modifiedDate === null) {
            $this->modifiedDate = $this->dateTime->date(self::MODIFIED_DATE_FORMAT);
        }

        return $this->modifiedDate;
    }
}
