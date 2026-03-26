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



namespace Mirasvit\Sorting\Plugin;

use Magento\Catalog\Model\Category;
use Magento\Elasticsearch\ElasticAdapter\SearchAdapter\Mapper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * Modify Elasticsearch query to place pinned products first in category results
 *
 * @see Mapper::buildQuery()
 */
class ElasticsearchPinnedProductSortingPlugin
{
    private $registry;

    private $pinnedProductService;

    private $request;

    public function __construct(
        Registry             $registry,
        PinnedProductService $pinnedProductService,
        RequestInterface     $request
    ) {
        $this->registry             = $registry;
        $this->pinnedProductService = $pinnedProductService;
        $this->request              = $request;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterBuildQuery(Mapper $subject, array $result): array
    {
        if ($this->request->getFullActionName() !== 'catalog_category_view') {
            return $result;
        }

        $category = $this->registry->registry('current_category');

        if (!$category instanceof Category) {
            return $result;
        }

        $categoryId = (int)$category->getId();

        if (!$categoryId) {
            return $result;
        }

        $pinnedIds = $this->pinnedProductService->getProductIds($categoryId);

        if (empty($pinnedIds)) {
            return $result;
        }

        $result = $this->addPinningSortToQuery($result, $pinnedIds);

        return $result;
    }

    private function addPinningSortToQuery(array $query, array $pinnedIds): array
    {
        if (!isset($query['body']['sort'])) {
            return $query;
        }

        $originalSort = $query['body']['sort'];

        // Create script sort that returns 0 for pinned products, 1 for others
        $pinningSort = [
            '_script' => [
                'type'   => 'number',
                'script' => [
                    'source' => "params.pinnedIds.contains(doc['_id'].value) ? 0 : 1",
                    'params' => [
                        'pinnedIds' => array_map('strval', $pinnedIds),
                    ],
                ],
                'order'  => 'asc',
            ],
        ];

        $query['body']['sort'] = array_merge([$pinningSort], $originalSort);

        return $query;
    }
}
