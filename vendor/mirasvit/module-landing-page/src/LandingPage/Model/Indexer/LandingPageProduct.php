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

namespace Mirasvit\LandingPage\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Mirasvit\LandingPage\Model\Config\ConfigProvider;
use Mirasvit\LandingPage\Model\Indexer\LandingPageProduct\Indexer;

class LandingPageProduct implements ActionInterface, MviewActionInterface
{
    const INDEXER_ID = 'mst_landing_page_product';

    private $fullAction;

    private $resource;

    private $configProvider;

    public function __construct(
        Indexer            $fullAction,
        ResourceConnection $resource,
        ConfigProvider     $configProvider
    ) {
        $this->fullAction     = $fullAction;
        $this->resource       = $resource;
        $this->configProvider = $configProvider;
    }

    public function execute($ids): void
    {
        $affectedPageIds = $this->resolveAffectedPageIds((array)$ids);

        if (empty($affectedPageIds)) {
            return;
        }

        $this->fullAction->execute($affectedPageIds);
    }

    public function executeFull(): void
    {
        $this->fullAction->execute();
    }

    public function executeList(array $ids): void
    {
        $this->execute($ids);
    }

    public function executeRow($id): void
    {
        $this->execute([$id]);
    }

    private function resolveAffectedPageIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        if (!$this->configProvider->isRelatedPagesEnabled()) {
            return [];
        }

        $ids = array_map('intval', $ids);

        $connection = $this->resource->getConnection();
        $indexTable = $this->resource->getTableName(ConfigProvider::INDEX_TABLE);
        $pageTable  = $this->resource->getTableName('mst_landing_page');

        $validPageIds = $connection->fetchCol(
            $connection->select()
                ->from($pageTable, ['page_id'])
                ->where('page_id IN (?)', $ids)
        );

        $pageIdsFromProducts = $connection->fetchCol(
            $connection->select()
                ->from($indexTable, ['page_id'])
                ->distinct()
                ->where('product_id IN (?)', $ids)
        );

        return array_values(array_unique(array_merge(
            array_map('intval', $validPageIds),
            array_map('intval', $pageIdsFromProducts)
        )));
    }
}
