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

namespace Mirasvit\LayeredNavigation\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Mirasvit\Core\Service\CompatibilityService;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\SizeLimiterConfigProvider;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryFilter extends AbstractFilter
{
    const ATTRIBUTE = 'category_ids';
    const CATEGORY  = 'category';

    private $dataProvider;

    private $treeBuilder;

    private $request;

    private $extraFilterConfigProvider;

    private $sizeLimiterConfigProvider;

    private $scopeConfig;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AttributeRepository $attributeRepository,
        CategoryFilter\TreeBuilder $treeBuilder,
        CategoryFactory $categoryDataProviderFactory,
        RequestInterface $request,
        ExtraFilterConfigProvider $extraFilterConfigProvider,
        SizeLimiterConfigProvider $sizeLimiterConfigProvider,
        Layer $layer,
        Context $context,
        array $data = []
    ) {
        parent::__construct($layer, $context, $data);

        $this->_requestVar  = 'cat';
        $this->treeBuilder  = $treeBuilder;
        $this->dataProvider = $categoryDataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->request      = $request;

        $this->extraFilterConfigProvider = $extraFilterConfigProvider;
        $this->sizeLimiterConfigProvider = $sizeLimiterConfigProvider;
        $this->scopeConfig               = $scopeConfig;

        $this->setAttributeModel($attributeRepository->get('category_ids'));
    }

    public function apply(RequestInterface $request): self
    {
        $categoryId = $request->getParam($this->getRequestVar()) ? : $request->getParam('id');

        if (empty($categoryId)) {
            return $this;
        }

        $categoryIds = explode(',', (string)$categoryId);
        $categoryIds = array_unique($categoryIds);
        $categoryIds = array_filter($categoryIds, 'is_numeric');
        $categoryIds = array_diff($categoryIds, ['', 0, false, null]); //don't use incorrect data

        if ($request->getParam('id') != $categoryId) {
            $this->getProductCollection()
                ->addFieldToFilter('category_ids', $categoryIds);

            // fix for Magento 2.4.5 error on search result page after filtering by category with "Display Out of Stock Products" enabled
            // @see Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplier::getProductIdsBySaleability()
            list($a, $b, $c) = explode('.', CompatibilityService::getVersion());

            // to consider patched versions like 2.4.5-p1 as 2.4.5
            $is245 = (int)$a == 2 && (int)$b == 4 && (int)$c == 5;

            if (
                $is245 && $this->hasShowOutOfStockStatus()
                && strpos($request->getFullActionName(), 'search') !== false
            ) {
                $this->getProductCollection()->setFlag('has_category_filter', false);
                $this->getProductCollection()->setFlag('has_stock_status_filter', false);
            }

            $category = $this->getLayer()->getCurrentCategory();
            /** @var \Magento\Catalog\Model\ResourceModel\AbstractCollection $collection */
            $collection = $category->getCollection();
            $child      = $collection
                ->addFieldToFilter($category->getIdFieldName(), $categoryIds)
                ->addAttributeToSelect('name');
            $this->addState(false, $categoryIds, $child);
        }

        return $this;
    }

    public function getName(): string
    {
        return (string)__('Category');
    }

    /**
     * Add data to state
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param string|bool                                                  $categoryName
     * @param array<int, int>                                              $categoryId
     * @param bool|\Magento\Catalog\Model\ResourceModel\AbstractCollection $child
     *
     * @return bool
     */
    protected function addState($categoryName, $categoryId, $child = false)
    {
        if (is_array($categoryId) && $child && $this->stateBarConfigProvider->isFilterClearBlockInOneRow()) {
            $labels = [];
            foreach ($categoryId as $categoryIdValue) {
                if ($currentCategory = $child->getItemById($categoryIdValue)) {
                    $labels[] = $currentCategory->getName();
                }
            }
            $this->addStateItem(
                $this->_createItem(
                    implode(', ', $labels),
                    $categoryId
                )
            );
        } elseif (is_array($categoryId) && $child) {
            foreach ($categoryId as $categoryIdValue) {
                if ($currentCategory = $child->getItemById($categoryIdValue)) {
                    $this->addStateItem(
                        $this->_createItem(
                            $currentCategory->getName(),
                            $categoryIdValue
                        )
                    );
                }
            }
        } else {
            $this->addStateItem(
                $this->_createItem(
                    $categoryName,
                    $categoryId
                )
            );
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _initItems(): self
    {
        $configDependencies = [
            'brand_brand_view'              => '\Mirasvit\Brand\Model\Config\GeneralConfig',
            'all_products_page_index_index' => '\Mirasvit\AllProducts\Config\Config'
        ];

        $this->_items = [];

        $category = $this->dataProvider->getCategory();

        if (!$category->getIsActive()) {
            return $this;
        }

        if (
            $this->request->getParam($this->getRequestVar())
            && !$this->configProvider->isMultiselectEnabled(self::ATTRIBUTE)
        ) {
            return $this; // do not show category filter when multiselect is disabled and category filter already selected
        }

        $isShowNestedCategories = $this->isShowNestedCategories();

        $optionsFacetedData = $this->getProductCollection()->getExtendedFacetedData(
            self::CATEGORY,
            $this->configProvider->isMultiselectEnabled(self::ATTRIBUTE)
        );

        $items = $this->treeBuilder->getItems(
            $category,
            $optionsFacetedData,
            $isShowNestedCategories
        );

        if (!$isShowNestedCategories && $this->isSortByLabel()) {
            usort($items, function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
        }

        if (!$isShowNestedCategories && $this->isSortByCount()) {
            $items = $this->sortByCounts($items, $optionsFacetedData);
        }

	    $fullActionName = $this->request->getFullActionName();

        foreach ($items as $data) {
            // Brands and AllProducts isShowAllCategories resolver
            if (in_array($fullActionName, array_keys($configDependencies))) {
                $config = ObjectManager::getInstance()->get($configDependencies[$fullActionName]);

                if (!$config->isShowAllCategories()) {
                    if ($data['level'] > 0 || $data['count'] == 0) {
                        continue;
                    }
                }
            }

            if (!$isShowNestedCategories) {
                $data['level'] = 0;

                if ($data['count'] === 0) {
                    continue;
                }
            }

            $item = $this->_createItem($data['label'], $data['value'], $data['count']);
            $item->addData($data);

            $this->_items[] = $item;
        }

        return $this;
    }

    public function isSortByLabel(): bool
    {
        return $this->extraFilterConfigProvider->getCategoryFilterSortOptionsBy() === AttributeConfigInterface::OPTION_SORT_BY_LABEL;
    }

    public function isSortByCount(): bool
    {
        return $this->extraFilterConfigProvider->getCategoryFilterSortOptionsBy() === AttributeConfigInterface::OPTION_SORT_BY_COUNT;
    }

    public function isUseAlphabeticalIndex(): bool
    {
        return $this->extraFilterConfigProvider->isUseAlphabeticalIndexForCategoryFilter();
    }

    public function getAlphabeticalLimit(): int
    {
        return $this->sizeLimiterConfigProvider->getAlphabeticalLimit();
    }

    public function isAlphabeticalIndexAllowedByLimit(): bool
    {
        return $this->getAlphabeticalLimit() <= count($this->getItems());
    }

    public function isShowNestedCategories(): bool
    {
        return $this->extraFilterConfigProvider->isShowNestedCategories();
    }

    private function hasShowOutOfStockStatus(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            \Magento\CatalogInventory\Model\Configuration::XML_PATH_SHOW_OUT_OF_STOCK,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
