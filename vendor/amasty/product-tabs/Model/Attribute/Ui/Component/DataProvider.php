<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Attribute\Ui\Component;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\RequestInterface;

/**
 * Data provider
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var \Amasty\CustomTabs\Model\Attribute\ItemsProvider
     */
    private $itemsProvider;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        \Amasty\CustomTabs\Model\Attribute\ItemsProvider $itemsProvider,
        array $meta = [],
        array $data = []
    ) {
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
        $this->itemsProvider = $itemsProvider;
    }

    /**
     * Sort attributes array by field.
     *
     * @param array $items
     * @param string $field
     * @param string $direction
     * @return array
     */
    private function sortBy($items, $field, $direction)
    {
        usort($items, function ($item1, $item2) use ($field, $direction) {
            return $this->attributesCompare($item1, $item2, $field, $direction);
        });
        return $items;
    }

    /**
     * Compare attributes array's elements on index.
     *
     * @param array $attribute1
     * @param array $attribute2
     * @param string $partIndex
     * @param string $direction
     *
     * @return int
     */
    private function attributesCompare($attribute1, $attribute2, $partIndex, $direction)
    {
        $values = [$attribute1[$partIndex], $attribute2[$partIndex]];
        sort($values, SORT_STRING);
        return $attribute1[$partIndex] === $values[$direction == SortOrder::SORT_ASC ? 0 : 1] ? -1 : 1;
    }

    /**
     * Merge attributes from different sources:
     * custom attributes and default (stores configuration attributes)
     *
     * @return array
     */
    public function getData()
    {
        $searchCriteria = $this->getSearchCriteria();
        $sortOrders = $searchCriteria->getSortOrders();

        $items = $this->itemsProvider->execute();

        /** @var \Magento\Framework\Api\SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            if ($sortOrder->getField() && $sortOrder->getDirection()) {
                $items = $this->sortBy($items, $sortOrder->getField(), $sortOrder->getDirection());
            }
        }

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $value = str_replace('%', '', $filter->getValue());
                foreach (['label', 'code'] as $filterField) {
                    $items = array_values(
                        array_filter(
                            $items,
                            function ($item) use ($value, $filterField) {
                                return strpos(strtolower($item[$filterField]), strtolower($value)) !== false;
                            }
                        )
                    );
                }
            }
        }

        return [
            'items' => $items
        ];
    }
}
