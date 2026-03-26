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

namespace Mirasvit\Sorting\Plugin\Frontend;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\PageBuilder\Model\Catalog\Sorting;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Mirasvit\Sorting\Service\CriteriaApplierService;
use Mirasvit\Sorting\Api\Data\CriterionInterface;

/**
 * @see Sorting::applySorting
 */
class ApplyPageBuilderSortingPlugin
{
    private $criterionRepository;

    private $criteriaApplierService;

    private $moduleManager;

    public function __construct(
        CriterionRepository $criterionRepository,
        CriteriaApplierService $criteriaApplierService,
        ModuleManager $moduleManager
    ) {
        $this->criterionRepository    = $criterionRepository;
        $this->criteriaApplierService = $criteriaApplierService;
        $this->moduleManager          = $moduleManager;
    }

    public function aroundApplySorting(
        Sorting $subject,
        $proceed,
        string $option,
        Collection $collection
    ): Collection {
        $this->criteriaApplierService->setGlobalRankingFactors($collection);

        $criterion = $this->criterionRepository->getByCode($option);

        if ($criterion) {
            $this->criteriaApplierService->setCriterion($collection, $criterion);

            $this->applyElasticsuiteSorting($collection, $option, $criterion);

            if ($collection->isLoaded()) {
                $collection->clear();
            }

            return $collection;
        }

        return $proceed($option, $collection);
    }

    private function applyElasticsuiteSorting(
        Collection $collection,
        string $criterionCode,
        CriterionInterface $criterion
    ): void {
        if (!$this->isElasticsuiteCollection($collection)) {
            return;
        }

        $frames = $criterion->getConditionCluster()->getFrames();
        $direction = 'desc';

        if (!empty($frames)) {
            $firstFrame = reset($frames);
            $direction = $firstFrame->getDirection() ?: 'desc';
        }

        $collection->setOrder($criterionCode, $direction);
    }

    private function isElasticsuiteCollection(Collection $collection): bool
    {
        if (!$this->moduleManager->isEnabled('Smile_ElasticsuiteCatalog')) {
            return false;
        }

        return method_exists($collection, 'setSearchQuery');
    }
}
