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

namespace Mirasvit\Brand\Ui\BrandPage\Listing;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as UiDataProvider;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Api\Data\BrandPageStoreInterface;
use Mirasvit\Brand\Service\ImageUrlService;
use Magento\Framework\UrlInterface;
use Magento\Framework\Api\Filter;

class DataProvider extends UiDataProvider
{
    private $imageUrlService;

    private $urlBuilder;

    private $collection;

    public function __construct(
        ImageUrlService $imageUrlService,
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        UrlInterface $urlBuilder,
        SearchResultInterface $collection,
        array $meta = [],
        array $data = []
    ) {
        $this->imageUrlService = $imageUrlService;
        $this->urlBuilder      = $urlBuilder;
        $this->collection      = $collection;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    public function getSearchResult(): SearchResultInterface
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $items = [];

        foreach ($searchResult->getItems() as $item) {
            $itemData = $item->getData();

            $itemData['product_count'] = $item->getData('product_count');
            $itemData = $this->prepareLogoData($item, $itemData);

            $itemData[BrandPageInterface::BRAND_TITLE] =
                $item->getStoreValue(BrandPageStoreInterface::BRAND_TITLE, 0);

            $itemData[BrandPageStoreInterface::STORE_ID] =
                $itemData[BrandPageInterface::STORE_IDS] ?? '0';

            $items[] = $itemData;
        }

        return [
            'items' => $items,
            'totalRecords' => $searchResult->getTotalCount(),
        ];
    }

    private function prepareLogoData($item, array $itemData): array
    {
        $originalLogo = $item->getData(BrandPageInterface::LOGO);

        if (!$originalLogo) {
            return $itemData;
        }

        $imageUrl = $this->imageUrlService->getImageUrl($originalLogo);
        $alt = $item->getStoreValue(BrandPageStoreInterface::BRAND_TITLE, 0) ?? '';

        $itemData[BrandPageInterface::LOGO] = $originalLogo;
        $itemData[BrandPageInterface::LOGO . '_src'] = $imageUrl;
        $itemData[BrandPageInterface::LOGO . '_orig_src'] = $imageUrl;
        $itemData[BrandPageInterface::LOGO . '_alt'] = $alt;
        $itemData[BrandPageInterface::LOGO . '_link'] = $this->urlBuilder->getUrl(
            'brand/brand/edit',
            ['id' => $item->getId()]
        );

        return $itemData;
    }

    public function addFilter(Filter $filter)
    {
        $field = $filter->getField();
        $condition = $filter->getConditionType() ?? 'eq';

        if ($field === 'store_id') {
            $this->collection->addStoreFilter($filter->getValue());
            return;
        }

        $this->collection->addFieldToFilter($field, [$condition => $filter->getValue()]);
    }

    public function getData()
    {
        $sortField = $this->request->getParam('sorting');

        if (is_array($sortField) && isset($sortField['field'], $sortField['direction'])) {
            $this->collection->addOrder($sortField['field'], $sortField['direction']);
        }

        return $this->searchResultToOutput($this->collection);
    }
}
