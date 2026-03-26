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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Ui\Page\Listing;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Model\ResourceModel\Filter\Collection;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Mirasvit\LandingPage\Repository\PageRepository;
use Mirasvit\LandingPage\Service\ImageUrlService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private $attributeRepository;

    private $categoryRepository;

    private $filterRepository;

    private $pageRepository;

    private $imageUrlService;

    /**
    * @SuppressWarnings(PHPMD.ExcessiveParameterList)
    */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        PageRepository              $pageRepository,
        AttributeRepository         $attributeRepository,
        FilterRepository            $filterRepository,
        ImageUrlService             $imageUrlService,
        string                      $name,
        string                      $primaryFieldName,
        string                      $requestFieldName,
        ReportingInterface          $reporting,
        SearchCriteriaBuilder       $searchCriteriaBuilder,
        RequestInterface            $request,
        FilterBuilder               $filterBuilder,
        array                       $meta = [],
        array                       $data = []
    ) {
        $this->categoryRepository  = $categoryRepository;
        $this->pageRepository      = $pageRepository;
        $this->attributeRepository = $attributeRepository;
        $this->filterRepository    = $filterRepository;
        $this->imageUrlService     = $imageUrlService;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
    }

    public function getPage(int $pageId): PageInterface
    {
        return $this->pageRepository->get($pageId);
    }

    public function getFilters(int $pageId): Collection
    {
        return $this->filterRepository->getByPageId($pageId);
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems          = [];
        $arrItems['items'] = [];

        foreach ($searchResult->getItems() as $page) {
            $pageData                           = $page->getData();
            $pageData[PageInterface::IS_ACTIVE] = $pageData[PageInterface::IS_ACTIVE] ? 'Enabled' : 'Disabled';
            $pageData[PageInterface::REDIRECT]  = $pageData[PageInterface::REDIRECT] ? 'Enabled' : 'Disabled';
            $pageData[PageInterface::IMAGE . '_src'] = $this->imageUrlService->getImageUrl($page->getImage());
            $pageData[PageInterface::IMAGE . '_orig_src'] = $this->imageUrlService->getImageUrl($page->getImage());
            $pageData['store_view']             = explode(',', $pageData[PageInterface::STORE_IDS]);
            $pageData['col_attributes']         = !empty($attributes = $this->getAttributes((int)$pageData[PageInterface::PAGE_ID])) ? $attributes : null;
            $pageData['col_categories']         = $this->getCategories($this->getPage((int)$pageData[PageInterface::PAGE_ID])) ?? null;
            $pageData['col_search_term']        = $this->getPage((int)$pageData[PageInterface::PAGE_ID])->getSearchTerm() ?? null;
            $pageData['products']               = !$pageData['col_attributes'] && !$pageData['col_categories'] && !$pageData['col_search_term'];
            $arrItems['items'][]                = $pageData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }

    private function getAttributes(int $pageId): array
    {
        $filterArray = [];
        $filters     = $this->getFilters($pageId);

        if (!count($filters)) {
            return $filterArray;
        }

        foreach ($filters as $filter) {
            $optionLabels = [];
            $attribute    = $this->attributeRepository->get($filter->getAttributeId());
            $options      = explode(',', $filter->getOptionIds());

            foreach ($options as $option) {
                if ($optionLabel = $this->getOptionLabel($attribute, (int)$option)) {
                    $optionLabels[] = $optionLabel;
                }
            }

            $filterArray[] = [
                'name'    => $attribute->getDefaultFrontendLabel(),
                'options' => implode(', ', $optionLabels),
            ];
        }


        return $filterArray;
    }

    private function getOptionLabel(AttributeInterface $attribute, int $optionId): ?string
    {
        $options = $attribute->getOptions();

        foreach ($options as $option) {
            if ($option->getValue() == $optionId) {
                return (string)$option->getLabel();
            }
        }

        return null;
    }

    private function getCategories(PageInterface $page): string
    {
        $categories = [];

        if (!$page->getCategories()) {
            return '';
        }

        $categoryIds = explode(',', $page->getCategories());

        foreach ($categoryIds as $categoryId) {
            $category     = $this->categoryRepository->get($categoryId);
            $categories[] = $category->getName();
        }

        return implode(', ', $categories);
    }
}
