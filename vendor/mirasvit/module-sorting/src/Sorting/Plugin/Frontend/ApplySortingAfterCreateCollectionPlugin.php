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
use Magento\CatalogWidget\Block\Product\ProductsList;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Mirasvit\Sorting\Service\CriteriaApplierService;

/**
 * @see ProductsList::createCollection
 */
class ApplySortingAfterCreateCollectionPlugin
{
    private $criterionRepository;

    private $criteriaApplierService;

    public function __construct(
        CriterionRepository    $criterionRepository,
        CriteriaApplierService $criteriaApplierService
    ) {
        $this->criterionRepository    = $criterionRepository;
        $this->criteriaApplierService = $criteriaApplierService;
    }

    public function afterCreateCollection(ProductsList $subject, Collection $collection): Collection
    {
        if (!$collection) {
            return $collection;
        }

        // Mark collection as widget-sourced to prevent category pinning
        $collection->setFlag(CriteriaApplierService::FLAG_IS_WIDGET, true);

        $this->criteriaApplierService->setGlobalRankingFactors($collection);

        $sortBy = $subject->getData('sort_by');

        if ($sortBy) {
            $criterion = $this->criterionRepository->getByCode($sortBy);

            if (!$criterion) {
                return $collection;
            }

            $this->criteriaApplierService->setCriterion($collection, $criterion);
        }

        return $collection;
    }
}
