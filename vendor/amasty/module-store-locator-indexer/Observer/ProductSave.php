<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Store Locator Indexer for Magento 2 (System)
 */

namespace Amasty\StorelocatorIndexer\Observer;

use Amasty\StorelocatorIndexer\Model\Indexer\Product\IndexBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Class ProductSave execute when Save Product
 */
class ProductSave implements ObserverInterface
{
    /**
     * @var IndexBuilder
     */
    private $indexBuilder;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    public function __construct(
        IndexBuilder $indexBuilder,
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexBuilder = $indexBuilder;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if (($product = $observer->getEvent()->getProduct()) && !$this->isIndexerScheduled()) {
            $this->indexBuilder->reindexByProductId($product->getId());
        }
    }

    private function isIndexerScheduled(): bool
    {
        return $this->indexerRegistry->get(IndexBuilder::INDEXER_ID)->isScheduled();
    }
}
