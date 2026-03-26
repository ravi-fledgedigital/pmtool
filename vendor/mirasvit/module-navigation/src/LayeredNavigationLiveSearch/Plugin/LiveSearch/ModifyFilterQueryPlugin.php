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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\LayeredNavigationLiveSearch\Plugin\LiveSearch;


use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\LiveSearchAdapter\Model\QueryArgumentProcessor\FilterQueryArgumentProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;


class ModifyFilterQueryPlugin
{
    private $request;

    private $storeManager;

    private $categoryRepository;

    public function __construct(
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->request            = $request;
        $this->storeManager       = $storeManager;
        $this->categoryRepository = $categoryRepository;
    }

    public function afterGetQueryArgumentValue(FilterQueryArgumentProcessor $subject, array $result): array
    {
        foreach ($result as $idx => $filterData) {
            // brands and all products
            if ($this->isActionApplicable() && $filterData['attribute'] == 'categoryPath' && !$filterData['eq']) {
                $store        = $this->storeManager->getStore();
                $rootCategory = $this->categoryRepository->get($store->getRootCategoryId(), $store->getId());

                $result[$idx]['eq'] = $rootCategory->getUrlKey();
            }

            if ($filterData['attribute'] !== ExtraFilterConfigProvider::RATING_FILTER) {
                continue;
            }

            $minValue  = min($filterData['in']);
            $newValues = range($minValue, 5);

            $result[$idx]['in'] = $newValues;
        }

        $result = array_values($result);

        return $result;
    }

    private function isActionApplicable(): bool
    {
        return in_array($this->request->getFullActionName(), ['brand_brand_view', 'all_products_page_index_index']);
    }
}
