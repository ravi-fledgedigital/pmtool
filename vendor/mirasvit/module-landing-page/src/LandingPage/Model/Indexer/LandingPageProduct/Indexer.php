<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Model\Indexer\LandingPageProduct;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Model\Config\ConfigProvider;
use Mirasvit\LandingPage\Model\Indexer\LandingPageProduct\ProductResolver;
use Mirasvit\LandingPage\Repository\PageRepository;
use Psr\Log\LoggerInterface;

class Indexer
{
    private $resource;

    private $pageRepository;

    private $storeManager;

    private $productResolver;

    private $logger;

    private $configProvider;

    public function __construct(
        ResourceConnection    $resource,
        PageRepository        $pageRepository,
        StoreManagerInterface $storeManager,
        ProductResolver       $productResolver,
        LoggerInterface       $logger,
        ConfigProvider        $configProvider
    ) {
        $this->resource        = $resource;
        $this->pageRepository  = $pageRepository;
        $this->storeManager    = $storeManager;
        $this->productResolver = $productResolver;
        $this->logger          = $logger;
        $this->configProvider  = $configProvider;
    }

    /**
     * @param int[]|null $pageIds Specific page IDs to reindex, or null for full reindex.
     */
    public function execute(?array $pageIds = null): void
    {
        $connection = $this->resource->getConnection();
        $tableName  = $this->resource->getTableName(ConfigProvider::INDEX_TABLE);

        if (!$this->configProvider->isRelatedPagesEnabled()) {
            if ($pageIds === null) {
                $connection->truncateTable($tableName);
            }

            return;
        }

        $stores = $this->storeManager->getStores();
        $activePageIds = [];

        foreach ($stores as $store) {
            $storeId = (int)$store->getId();

            $pages = $this->pageRepository->getCollection()
                ->addStoreFilter($storeId)
                ->addFieldToFilter(PageInterface::IS_ACTIVE, 1);

            if ($pageIds !== null) {
                $pages->addFieldToFilter(PageInterface::PAGE_ID, ['in' => $pageIds]);
            }

            foreach ($pages as $page) {
                $currentPageId = (int)$page->getPageId();
                $activePageIds[$currentPageId] = true;

                try {
                    $productIds = $this->productResolver->resolve($page, $storeId);
                } catch (\Exception $e) {
                    $this->logger->error(sprintf(
                        'Landing page index error for page #%d, store #%d: %s',
                        $currentPageId,
                        $storeId,
                        $e->getMessage()
                    ));

                    continue;
                }

                $connection->delete($tableName, [
                    'page_id = ?'  => $currentPageId,
                    'store_id = ?' => $storeId,
                ]);

                $this->insertBatch($connection, $tableName, $currentPageId, $productIds, $storeId);
            }
        }

        // Full reindex: remove records for deleted/inactive pages only
        if ($pageIds === null && !empty($activePageIds)) {
            $connection->delete($tableName, [
                'page_id NOT IN (?)' => array_keys($activePageIds),
            ]);
        } elseif ($pageIds === null && empty($activePageIds)) {
            $connection->truncateTable($tableName);
        }
    }

    private function insertBatch(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        $table,
        $pageId,
        array $productIds,
        $storeId
    ): void {
        if (empty($productIds)) {
            return;
        }

        $data = [];
        foreach ($productIds as $productId) {
            $data[] = [
                'page_id'    => $pageId,
                'product_id' => (int)$productId,
                'store_id'   => $storeId,
            ];
        }

        foreach (array_chunk($data, 1000) as $chunk) {
            $connection->insertOnDuplicate($table, $chunk, ['page_id']);
        }
    }
}
