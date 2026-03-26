<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

use Magento\CatalogPermissions\Model\Indexer\Category as CategoryIndexer;
use Magento\CatalogPermissions\Model\Indexer\Product as ProductIndexer;

class Import
{
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface
     */
    protected $config;

    /**
     * @param \Magento\CatalogPermissions\App\ConfigInterface $config
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     */
    public function __construct(
        \Magento\CatalogPermissions\App\ConfigInterface $config,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
    ) {
        $this->config = $config;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * After import handler
     *
     * @param \Magento\ImportExport\Model\Import $subject
     * @param bool $import
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImportSource(\Magento\ImportExport\Model\Import $subject, $import)
    {
        if ($this->config->isEnabled()) {
            $categoryIndexer = $this->indexerRegistry->get(CategoryIndexer::INDEXER_ID);
            if (!$categoryIndexer->isScheduled()) {
                $categoryIndexer->invalidate();
            }
            $productIndexer = $this->indexerRegistry->get(ProductIndexer::INDEXER_ID);
            if (!$productIndexer->isScheduled()) {
                $productIndexer->invalidate();
            }
        }
        return $import;
    }
}
