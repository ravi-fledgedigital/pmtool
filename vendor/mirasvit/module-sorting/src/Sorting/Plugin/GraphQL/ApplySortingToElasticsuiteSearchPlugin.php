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

namespace Mirasvit\Sorting\Plugin\GraphQL;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Mirasvit\Sorting\Service\CriteriaApplierService;
use Mirasvit\Sorting\Service\GraphQl\CategoryContextService;
use Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products\Query\Search;

/**
 * @see Search::getResult
 */
class ApplySortingToElasticsuiteSearchPlugin
{
    private $criteriaApplierService;

    private $categoryContextService;

    public function __construct(
        CriteriaApplierService $criteriaApplierService,
        CategoryContextService $categoryContextService
    ) {
        $this->criteriaApplierService = $criteriaApplierService;
        $this->categoryContextService = $categoryContextService;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGetResult(
        Search           $subject,
        array            $args,
        ResolveInfo      $info,
        ContextInterface $context
    ): array {
        $this->setCategoryContext($args);

        $mstSort = $args['sort']['mst_sort'] ?? null;

        if ($mstSort && isset($mstSort['code'], $mstSort['dir'])) {
            $attribute = null;

            if (!empty($mstSort['code'])) {
                $attribute = $mstSort['code'];
            } else {
                $defaultCriterion = $this->criteriaApplierService->getDefaultCriterion();
                if ($defaultCriterion && $defaultCriterion->getCode()) {
                    $attribute = $defaultCriterion->getCode();
                }
            }
            $dir = $mstSort['dir'];

            $args['sort'] = $this->normalizeSortOrders([$attribute => $dir]);
        }

        return [$args, $info, $context];
    }

    private function normalizeSortOrders(array $orders): array
    {
        $newOrders = [];
        $rawOrders = $this->criteriaApplierService->prepareCriteria($orders);

        foreach ($rawOrders as $attribute => $dir) {
            $newOrders[is_object($dir) ? $dir->getField() : $attribute]
                = is_object($dir) ? $dir->getDirection() : $dir;
        }

        return $newOrders;
    }

    /**
     * Extract category ID and mst_pin flag from GraphQL args and set in category context
     */
    private function setCategoryContext(array $args): void
    {
        $filter = $args['filter'] ?? [];

        if (!empty($filter['category_id']['eq'])) {
            $this->categoryContextService->setCategoryId((int)$filter['category_id']['eq']);
        }

        if (!empty($filter['category_uid']['eq'])) {
            $this->categoryContextService->setCategoryUid($filter['category_uid']['eq']);
        }

        $pinToTop = $args['sort']['mst_pin'] ?? false;
        $this->categoryContextService->setPinToTopEnabled((bool)$pinToTop);
    }
}
