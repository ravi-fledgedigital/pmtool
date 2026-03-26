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

namespace Mirasvit\Sorting\Plugin;

use Mirasvit\Sorting\Api\Data\CriterionInterface;
use Mirasvit\Sorting\Api\Data\RankingFactorInterface;
use Mirasvit\Sorting\Model\Indexer;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Mirasvit\Sorting\Repository\RankingFactorRepository;
use Mirasvit\Sorting\Service\ScoreFetcherService;

/**
 * @SuppressWarnings(PHPMD)
 * @see \Magento\Elasticsearch\Model\Adapter\Elasticsearch::prepareDocsPerStore()
 */
class PutScoreAfterPrepareDocsPerStorePlugin
{
    private $rankingFactorRepository;

    private $scoreFetcherService;

    private $criterionRepository;

    public function __construct(
        CriterionRepository     $criterionRepository,
        RankingFactorRepository $rankingFactorRepository,
        ScoreFetcherService     $scoreCalculationService
    ) {
        $this->criterionRepository     = $criterionRepository;
        $this->rankingFactorRepository = $rankingFactorRepository;
        $this->scoreFetcherService     = $scoreCalculationService;
    }

    /**
     * @param object   $subject
     * @param Callable $proceed
     * @param array    $documentData
     * @param int      $storeId
     *
     * @return array
     */
    public function aroundPrepareDocsPerStore(object $subject, callable $proceed, array $documentData, int $storeId): array
    {
        $docs = $proceed($documentData, $storeId);

        return $this->putScore($docs, $storeId);
    }

    protected function putScore(array $docs, int $storeId): array
    {
        $productIds = array_keys($docs);

        //prevent errors between adding new factor/criteria and reindex
        for ($i = 1; $i <= 10; $i++) {
            foreach ($productIds as $id) {
                $docs[$id]['sorting_factor_' . $i] = 0.0001;
            }
        }

        $scoreList  = $this->scoreFetcherService->getProductsScoreList($productIds, $storeId);
        $collection = $this->rankingFactorRepository->getCollection()
            ->addFieldToFilter(RankingFactorInterface::IS_ACTIVE, true);

        foreach ($collection as $factor) {
            foreach ($productIds as $id) {
                $score = $this->scoreFetcherService->getScore(
                    $scoreList,
                    (int)$id,
                    Indexer::getScoreColumn($factor)
                );

                $docs[$id]['sorting_factor_' . $factor->getId()] = $score;
            }
        }

        $globalFactors = $this->rankingFactorRepository->getCollection();
        $globalFactors->addFieldToFilter(RankingFactorInterface::IS_ACTIVE, true)
            ->addFieldToFilter(RankingFactorInterface::IS_GLOBAL, true);

        foreach ($productIds as $id) {
            $globalScore = 0;
            foreach ($globalFactors as $factor) {
                $score = $this->scoreFetcherService->getScore(
                    $scoreList,
                    (int)$id,
                    Indexer::getScoreColumn($factor)
                );

                $globalScore += $score * $factor->getWeight();
            }

            $docs[$id]['sorting_global'] = $globalScore + 0.0001;
        }

        foreach ($this->criterionRepository->getCollection() as $criterion) {
            foreach ($productIds as $id) {
                $frameScores = $this->scoreFetcherService->getFrameScores($criterion, $scoreList, $id);
                foreach ($frameScores as $frameIdx => $score) {
                    $docs[$id]['sorting_criterion_' . $criterion->getId() . '_frame_' . $frameIdx] = $score;
                }
            }
        }

        return $docs;
    }
}
