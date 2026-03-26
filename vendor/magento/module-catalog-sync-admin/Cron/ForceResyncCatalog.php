<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSyncAdmin\Cron;

use Magento\SaaSCommon\Model\ResyncManager;
use Magento\SaaSCommon\Model\ResyncManagerPool;
use Psr\Log\LoggerInterface;

/**
 * Class to execute a full catalog re-sync
 *
 */
class ForceResyncCatalog implements ForceResyncCatalogInterface
{
    /**
     * Resync Manager Pools
     */
    private const PRODUCT_ATTRIBUTES_RESYNC_POOL = 'productattributes';
    private const PRODUCT_RESYNC_POOL = 'products';
    private const PRODUCT_OVERRIDES_RESYNC_POOL = 'productoverrides';
    private const PRICES_RESYNC_POOL = 'prices';
    private const SCOPE_CUSTOMER_GROUPS_RESYNC_POOL = 'scopesCustomerGroup';
    private const SCOPE_WEBSITE_RESYNC_POOL = 'scopesWebsite';

    /**
     * @var ResyncManagerPool
     */
    private $resyncManagerPool;

    /**
     * @var ResyncManager
     */
    private $productResync;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param ResyncManagerPool $resyncManagerPool
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ResyncManagerPool $resyncManagerPool,
        LoggerInterface $logger
    ) {
        $this->resyncManagerPool = $resyncManagerPool;
        $this->logger = $logger;
    }

    /**
     * Executes full catalog resync submission
     *
     */
    public function execute()
    {
        try {
            $this->logger->info("Initiating full catalog data re-sync");
            $this->resyncFeed(self::PRODUCT_ATTRIBUTES_RESYNC_POOL);
            $this->resyncFeed(self::PRODUCT_RESYNC_POOL);
            $this->resyncFeed(self::SCOPE_CUSTOMER_GROUPS_RESYNC_POOL);
            $this->resyncFeed(self::SCOPE_WEBSITE_RESYNC_POOL);
            $this->resyncFeed(self::PRICES_RESYNC_POOL);
            $this->resyncFeed(self::PRODUCT_OVERRIDES_RESYNC_POOL);
            $this->logger->info('Catalog data re-sync successfully finished');
        } catch (\Exception $ex) {
            $this->logger->error(sprintf ('An error occurred during catalog data re-sync: %s', $ex->getMessage()));
        }
    }

    /**
     * Resync feed
     *
     * @param string $feedName
     * @throws \Exception
     */
    private function resyncFeed(string $feedName): void
    {
        $this->logger->info(sprintf('Re-syncing feed: %s', $feedName));
        $this->productResync = $this->resyncManagerPool->getResyncManager($feedName);
        $this->productResync->executeFullResync();
    }
}
