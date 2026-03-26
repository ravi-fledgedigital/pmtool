<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Store Locator for Magento 2
 */

namespace Amasty\Storelocator\Cron;

use Amasty\Storelocator\Api\Data\LocationInterface;
use Amasty\Storelocator\Block\Location as LocationBlock;
use Amasty\Storelocator\Block\View\Location as ViewLocationBlock;
use Amasty\Storelocator\Model\Location as LocationModel;
use Amasty\Storelocator\Model\ResourceModel\Location\Collection as LocationCollection;
use Amasty\Storelocator\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class FlushCacheBySchedule
{
    private const MIDNIGHT = 0;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var LocationCollectionFactory
     */
    private $locationCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        BlockFactory $blockFactory,
        CacheInterface $cache,
        LocationCollectionFactory $locationCollectionFactory,
        LoggerInterface $logger,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezone
    ) {
        $this->blockFactory = $blockFactory;
        $this->cache = $cache;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->timezone = $timezone;
    }

    public function execute(): void
    {
        try {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $storeId = (int)$store->getId();
                $this->storeManager->setCurrentStore($storeId);
                $hours = (int)$this->timezone->date()->format('H');
                if ($hours === self::MIDNIGHT) {
                    $this->processStore($storeId);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    'Amasty Storelocator: Error in cache flush cron: %s',
                    $e->getMessage()
                )
            );
        } finally {
            $this->storeManager->setCurrentStore(null);
        }
    }

    private function processStore(int $storeId): void
    {
        try {
            $locationsWithSchedule = $this->getLocationsWithSchedule($storeId);
            if ($locationsWithSchedule->getSize() > 0) {
                $this->flushLocationBlockCache($storeId);
                $this->flushViewLocationBlockCache($storeId, $locationsWithSchedule);
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    'Amasty Storelocator: Error cron processing store %d: %s',
                    $storeId,
                    $e->getMessage()
                )
            );
        }
    }

    private function getLocationsWithSchedule(int $storeId): LocationCollection
    {
        /** @var LocationCollection $collection */
        $collection = $this->locationCollectionFactory->create();
        $collection->addFilterByStores([Store::DEFAULT_STORE_ID, $storeId]);
        $collection->addFieldToFilter(LocationInterface::STATUS, 1);
        $collection->addFieldToFilter(LocationInterface::SHOW_SCHEDULE, 1);
        $collection->addFieldToFilter(LocationInterface::SCHEDULE, ['notnull' => true]);

        return $collection;
    }

    private function flushLocationBlockCache(int $storeId): void
    {
        $tags = [LocationModel::CACHE_TAG . '_s_' . $storeId];

        try {
            $this->cache->clean($tags);

            /* Clear page cache by location store tag */
            /** @var LocationBlock $collection */
            $locationBlock = $this->blockFactory->createBlock(LocationBlock::class);
            $locationBlock->setIdentities($tags);
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $locationBlock]);
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    'Amasty Storelocator: Error cleaning block_html cache by tags for store %d: %s',
                    $storeId,
                    $e->getMessage()
                )
            );
        }
    }

    private function flushViewLocationBlockCache(int $storeId, LocationCollection $locationsWithSchedule): void
    {
        $tags = [];
        foreach ($locationsWithSchedule as $location) {
            $locationId = $location->getId();
            $tags[] = LocationModel::CACHE_TAG . '_s_' . $storeId . '_lid_' . $locationId;
        }

        try {
            $this->cache->clean($tags);

            /* Clear page cache by view location store tag */
            /** @var ViewLocationBlock $viewLocationBlock */
            $viewLocationBlock = $this->blockFactory->createBlock(ViewLocationBlock::class);
            $viewLocationBlock->setIdentities($tags);
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $viewLocationBlock]);
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    'Amasty Storelocator: Error cleaning block_html cache by tags for store %d: %s',
                    $storeId,
                    $e->getMessage()
                )
            );
        }
    }
}
