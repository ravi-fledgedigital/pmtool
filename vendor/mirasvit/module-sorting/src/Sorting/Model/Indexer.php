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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Mirasvit\Sorting\Api\Data\IndexInterface;
use Mirasvit\Sorting\Api\Data\RankingFactorInterface;
use Mirasvit\Sorting\Repository\RankingFactorRepository;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\FlagManager;

class Indexer implements IndexerActionInterface, MviewActionInterface, IdentityInterface
{
    const INDEXER_ID = 'mst_sorting';

    const INDEXER_SHOULD_REINDEX = 'mst_sorting_should_reindex';

    private $resource;

    private $eventManager;

    private $rankingFactorRepository;

    private $indexerRegistry;

    private $flagManager;

    public function __construct(
        ResourceConnection $resource,
        ManagerInterface $eventManager,
        RankingFactorRepository $rankingFactorRepository,
        IndexerRegistry $indexerRegistry,
        FlagManager $flagManager
    ) {
        $this->resource                = $resource;
        $this->eventManager            = $eventManager;
        $this->rankingFactorRepository = $rankingFactorRepository;
        $this->indexerRegistry         = $indexerRegistry;
        $this->flagManager             = $flagManager;
    }

    public static function getScoreColumn(RankingFactorInterface $rankingFactor): string
    {
        return self::getScoreColumnById($rankingFactor->getId());
    }

    public static function getScoreColumnById(int $id): string
    {
        return 'factor_' . $id . '_score';
    }

    public static function getValueColumn(RankingFactorInterface $rankingFactor): string
    {
        return self::getValueColumnById($rankingFactor->getId());
    }

    public static function getValueColumnById(int $id): string
    {
        return 'factor_' . $id . '_value';
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     *
     * @return void
     */
    public function execute($ids)
    {
        $this->executeList($ids);
    }

    public function executeFull()
    {
        if (!$this->shouldRunFullReindex()) {
            return;
        }

        $tableName = $this->rankingFactorRepository->getFullCollection()
            ->getResource()
            ->getTable(IndexInterface::TABLE_NAME);
        $this->resource->getConnection()->truncateTable($tableName);

        $this->executeRankingFactor();

        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this]);
    }

    public function executeRankingFactor(array $rankingFactorIds = [], array $productIds = [])
    {
        $collection = $this->rankingFactorRepository->getFullCollection();

        if ($rankingFactorIds) {
            $collection->addFieldToFilter(RankingFactorInterface::ID, $rankingFactorIds);
        }

        foreach ($collection as $rankingFactor) {
            $factor = $this->rankingFactorRepository->getFactor($rankingFactor->getType());

            if ($factor) {
                $factor->reindex($rankingFactor, $productIds);
            }
        }
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     *
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->executeRankingFactor([], $ids);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     *
     * @return void
     */
    public function executeRow($id)
    {
        $this->executeList([$id]);
    }

    public function getIdentities()
    {
        return [
            \Magento\Catalog\Model\Category::CACHE_TAG,
        ];
    }

    private function shouldRunFullReindex(): bool
    {
        try {
            $indexer = $this->indexerRegistry->get(self::INDEXER_ID);

            if (!$indexer->isScheduled()) {
                return true;
            }

            $wasChanged = (bool) $this->flagManager->getFlagData(self::INDEXER_SHOULD_REINDEX);

            if ($wasChanged) {
                $this->flagManager->saveFlag(self::INDEXER_SHOULD_REINDEX, false);
                return true;
            }

            $now = time();
            $lastUpdated = $indexer->getState()->getUpdated();

            if (is_string($lastUpdated)) {
                $lastUpdated = strtotime($lastUpdated);
            }

            if ($now - $lastUpdated > 24 * 3600) {
                return true;
            }

            $nightStart = strtotime('00:00:00');
            $nightEnd = strtotime('06:00:00');

            if ($now >= $nightStart && $now <= $nightEnd) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return true;
        }
    }
}
