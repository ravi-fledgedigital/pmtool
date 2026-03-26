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

use Magento\Catalog\Model\Category;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Mirasvit\Sorting\Service\CriteriaApplierService;
use Mirasvit\Sorting\Service\GraphQl\CategoryContextService;
use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * @see \Magento\Framework\Api\SearchCriteria::setSortOrders()
 */
class SmileElasticsuiteSortOrderPlugin
{
    private $criteriaApplierService;

    private $registry;

    private $pinnedProductService;

    private $request;

    private $categoryContextService;

    public function __construct(
        CriteriaApplierService $criteriaApplierService,
        Registry               $registry,
        PinnedProductService   $pinnedProductService,
        RequestInterface       $request,
        CategoryContextService $categoryContextService
    ) {
        $this->criteriaApplierService = $criteriaApplierService;
        $this->registry               = $registry;
        $this->pinnedProductService   = $pinnedProductService;
        $this->request                = $request;
        $this->categoryContextService = $categoryContextService;
    }

    /**
     * @param SearchCriteria $subject
     * @param array          $orders
     *
     * @return array
     */
    public function beforeBuildSordOrders($subject, $containerConfig, $orders): array
    {
        $orders = $this->applyPinnedProductSorting($orders);

        if (!$this->criteriaApplierService->shouldAffectOrders(false)) {
            return [$containerConfig, $orders];
        }

        $orders = $this->criteriaApplierService->prepareCriteria($orders, false);

        return [$containerConfig, $orders];
    }

    /**
     * For GraphQL requests, only apply pinning if mst_pin: true is set
     * For frontend category page requests always apply pinning
     */
    private function applyPinnedProductSorting($orders)
    {
        $isFrontendCategoryPage   = $this->request->getFullActionName() === 'catalog_category_view';
        $isGraphQLPinToTopEnabled = $this->categoryContextService->isPinToTopEnabled();

        if (!$isFrontendCategoryPage && !$isGraphQLPinToTopEnabled) {
            return $orders;
        }

        $categoryId = $this->getCategoryId();

        if (!$categoryId) {
            return $orders;
        }

        $pinnedIds = $this->pinnedProductService->getProductIds($categoryId);

        if (empty($pinnedIds)) {
            return $orders;
        }

        // Build scores map: pinned products get 0, others get high number
        $scores = [];
        foreach ($pinnedIds as $productId) {
            $scores[(int)$productId] = 0;
        }

        // Create script sort using ElasticSuite format
        $scriptSource = "if(params.scores.containsKey(doc['_id'].value)) { return params.scores[doc['_id'].value];} return 922337203685477600L";

        $pinningSort = [
            '_script' => [
                'lang'       => 'painless',
                'scriptType' => 'number',
                'source'     => $scriptSource,
                'params'     => [
                    'scores' => $scores,
                ],
                'direction'  => 'asc',
            ],
        ];

        $orders = $pinningSort + $orders;

        return $orders;
    }

    private function getCategoryId(): ?int
    {
        // Check GraphQL context first (set by SetPinningContextPlugin or ApplySortingToCollectionPlugin)
        $categoryId = $this->categoryContextService->getCategoryId();

        if ($categoryId) {
            return $categoryId;
        }

        // Check frontend category page
        if ($this->request->getFullActionName() !== 'catalog_category_view') {
            return null;
        }

        $category = $this->registry->registry('current_category');

        if (!$category instanceof Category) {
            return null;
        }

        $categoryId = (int)$category->getId();

        return $categoryId ? : null;
    }
}
