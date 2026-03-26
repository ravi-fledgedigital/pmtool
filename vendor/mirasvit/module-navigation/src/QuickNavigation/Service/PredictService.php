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

namespace Mirasvit\QuickNavigation\Service;

use Magento\Catalog\Model\Layer;
use Mirasvit\QuickNavigation\Api\Data\SequenceInterface;
use Mirasvit\QuickNavigation\Context;
use Mirasvit\QuickNavigation\Model\ConfigProvider;
use Mirasvit\QuickNavigation\Repository\SequenceRepository;
use Mirasvit\LayeredNavigation\Model\Layer\Filter\DecimalFilter;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;

class PredictService
{
    private $configProvider;

    private $filterList;

    private $sequenceRepository;

    private $context;

    public function __construct(
        ConfigProvider $configProvider,
        SequenceRepository $sequenceRepository,
        Context $context,
        Layer\FilterList $filterList
    ) {
        $this->configProvider     = $configProvider;
        $this->filterList         = $filterList;
        $this->sequenceRepository = $sequenceRepository;
        $this->context            = $context;
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @return Layer\Filter\Item[]
     */
    public function getFilterItems(): array
    {
        $filterList = [];

        foreach ($this->getSuitableSequences() as $sequence) {
            $items = $this->splitSequence($sequence->getSequence());

            foreach ($items as $item) {
                $attr  = $item['attribute'];
                $value = $item['value'];

                $key = $attr . ':' . $value;
                if (!isset($filterList[$key])) {
                    $filterList[$key] = 0;
                }

                $filterList[$key] += $sequence->getPopularity();
            }
        }

        uasort($filterList, function ($a, $b) {
            if ($a < $b) {
                return 1;
            } else if ($a == $b) {
                return 0;
            } else {
                return -1;
            }
        });

        $itemsList    = [];
        $totalCounter = 0;
        foreach ($filterList as $key => $popularity) {
            if ($totalCounter >= $this->configProvider->getTotalThreshold()) {
                break;
            }

            [$attr, $value] = explode(':', $key);

            if (!isset($itemsList[$attr])) {
                $itemsList[$attr] = [
                    'popularity' => 0,
                    'values'     => [],
                ];
            }

            $itemsList[$attr]['popularity'] += $popularity;

            if (count($itemsList[$attr]['values']) < $this->configProvider->getAttributeThreshold()) {
                $itemsList[$attr]['values'][] = $value;

                $totalCounter++;
            }
        }

        uasort($itemsList, function ($a, $b) {
            if ($a['popularity'] < $b['popularity']) {
                return 1;
            } else if ($a['popularity'] == $b['popularity']) {
                return 0;
            } else {
                return -1;
            }
        });

        $filterList = [];

        foreach ($this->context->getState()->getFilters() as $filterItem) {
            $filterList[$filterItem->getFilter()->getRequestVar() . '_' . (string)$filterItem->getValueString()] = $filterItem;
        }

        $layerFilters = $this->filterList->getFilters($this->context->getLayer());

        foreach ($itemsList as $attr => $data) {
            foreach ($data['values'] as $value) {
                foreach ($layerFilters as $filter) {
                    if ($filter instanceof DecimalFilter) {
                        $attributeConfig = $filter->getAttributeConfig($filter->getRequestVar());

                        if (!in_array(
                            $attributeConfig->getDisplayMode(),
                            [AttributeConfigInterface::DISPLAY_MODE_RANGE, AttributeConfigInterface::DISPLAY_MODE_LABEL]
                        )) {
                            continue;
                        }
                    }

                    /** @var Layer\Filter\Item $filterItem */
                    foreach ($filter->getItems() as $filterItem) {
                        if ($filterItem->getValueString() == $value && $filter->getRequestVar() == $attr) {
                            $filterList[$filter->getRequestVar() . '_' . $filterItem->getValueString()] = $filterItem;
                        }
                    }
                }
            }
        }

        return array_values($filterList);
    }

    /**
     * @return SequenceInterface[]|\Mirasvit\QuickNavigation\Model\ResourceModel\Sequence\Collection
     */
    private function getSuitableSequences()
    {
        $sequenceString = $this->context->getSequenceString();
        $sequenceLength = $this->context->getSequenceLength();

        $collection = $this->sequenceRepository->getCollection();
        $collection->addFieldToFilter(SequenceInterface::STORE_ID, $this->context->getStoreId())
            ->addFieldToFilter(SequenceInterface::CATEGORY_ID, $this->context->getCategoryId())
            ->setOrder(SequenceInterface::POPULARITY, 'desc')
            ->setPageSize(100);

        if ($sequenceLength > 0) {
            $sequenceList = explode('|', $sequenceString);
            $searchString = '+' . implode(' +', $sequenceList);
            $collection->getSelect()->where('MATCH(sequence) AGAINST(? IN BOOLEAN MODE)', $searchString);
        } else {
            $collection->addFieldToFilter(SequenceInterface::LENGTH, 1);
        }

        $ids = $collection->getColumnValues('sequence_id');

        if (empty($ids)) {
            return $this->sequenceRepository->getCollection()->addFieldToFilter('sequence_id', 0);
        }

        $result = $this->sequenceRepository->getCollection();
        $result->addFieldToFilter('sequence_id', ['in' => $ids])
            ->addFieldToFilter(SequenceInterface::STORE_ID, $this->context->getStoreId())
            ->addFieldToFilter(SequenceInterface::CATEGORY_ID, $this->context->getCategoryId())
            ->setOrder(SequenceInterface::POPULARITY, 'desc')
            ->setPageSize(100);

        return $result;
    }

    private function splitSequence(string $sequence): array
    {
        $items   = explode('|', $sequence);
        $filters = [];
        foreach ($items as $item) {
            if (count(explode(':', $item)) < 2) {
                continue;
            }

            [$code, $value] = explode(':', $item);

            $filters[] = [
                'attribute' => $code,
                'value'     => $value,
            ];
        }

        return $filters;
    }
}
