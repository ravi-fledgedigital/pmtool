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

namespace Mirasvit\Sorting\Factor;

use Mirasvit\Sorting\Api\Data\RankingFactorInterface;
use Mirasvit\Sorting\Model\Indexer\FactorIndexer;
use Magento\Store\Model\StoreManagerInterface;

class ReviewCountFactor implements FactorInterface
{
    use ScoreTrait;

    private $context;
    private $indexer;
    private $storeManager;

    public function __construct(
        Context $context,
        FactorIndexer $indexer,
        StoreManagerInterface $storeManager
    ) {
        $this->context = $context;
        $this->indexer = $indexer;
        $this->storeManager = $storeManager;
    }

    public function getName(): string
    {
        return 'Review Count';
    }

    public function getDescription(): string
    {
        return 'Rank products based on the number of approved reviews.';
    }

    public function getUiComponent(): ?string
    {
        return null;
    }

    public function reindex(RankingFactorInterface $rankingFactor, array $productIds): void
    {
        $resource   = $this->indexer->getResource();
        $connection = $resource->getConnection();

        $stores = $this->storeManager->getStores();

        $this->indexer->process($rankingFactor, $productIds, function () use ($stores, $resource, $connection, $productIds) {
            foreach ($stores as $store) {
                $storeId = (int)$store->getId();

                $select = $connection->select()
                    ->from(
                        ['e' => $resource->getTableName('catalog_product_entity')],
                        ['entity_id']
                    )->joinInner(
                        ['review' => $resource->getTableName('review')],
                        'review.entity_pk_value = e.entity_id',
                        ['review_count' => new \Zend_Db_Expr('COUNT(review.review_id)')]
                    )->joinInner(
                        ['review_store' => $resource->getTableName('review_store')],
                        'review_store.review_id = review.review_id',
                        []
                    )->where('review.status_id = 1')
                    ->where('review_store.store_id = ?', $storeId)
                    ->group('e.entity_id');

                if ($productIds) {
                    $select->where('e.entity_id IN (?)', $productIds);
                }

                $stmt = $connection->query($select);

                while ($row = $stmt->fetch()) {
                    $value = (float)$row['review_count'];

                    $score = $this->normalize($value, 0, 100);

                    $this->indexer->add((int)$row['entity_id'], $score, (string)(int)$value, $storeId);
                }
            }
        });
    }
}
