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

namespace Mirasvit\LayeredNavigation\Block\Navigation;

use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Framework\View\Element\Template;
use Mirasvit\LayeredNavigation\Model\Config\HorizontalBarConfigProvider;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;
use Mirasvit\LayeredNavigation\Model\Layer\Filter\SearchFilter;
use Magento\Catalog\Model\Layer\FilterList;

class FilterExpander extends Template
{
    protected $_template = 'Mirasvit_LayeredNavigation::navigation/filterExpander.phtml';

    private   $config;

    private   $filterList;

    private   $layerResolver;

    private   $horizontalBarConfigProvider;

    public function __construct(
        ConfigProvider $config,
        FilterList $filterList,
        LayerResolver $layerResolver,
        HorizontalBarConfigProvider $horizontalBarConfigProvider,
        Template\Context $context,
        array $data = []
    ) {
        $this->config                      = $config;
        $this->filterList                  = $filterList;
        $this->layerResolver               = $layerResolver;
        $this->horizontalBarConfigProvider = $horizontalBarConfigProvider;

        parent::__construct($context, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getOpenedFilterIndexes(): array
    {
        $indexes = [];
        $layer   = $this->layerResolver->get();
        $filters = $this->filterList->getFilters($layer);
        $limit   = $this->config->getOpenedFiltersLimit();
        $idx     = 0;

        $filtersPositionsConfig = $this->horizontalBarConfigProvider->getFilterNavPositions();

        foreach ($filters as $filter) {
            if (!$filter->getItemsCount()) {
                continue;
            }

            if ($filter instanceof SearchFilter) {
                $limit++;
            }

            if (
                isset($filtersPositionsConfig[$filter->getRequestVar()])
                && $filtersPositionsConfig[$filter->getRequestVar()] == HorizontalBarConfigProvider::POSITION_HORIZONTAL
            ) {
                $idx++;
                continue;
            }

            if ($this->isActiveFilter($filter) || ($this->config->isOpenFilter() && $limit > 0)) {
                $indexes[] = $idx;

                if ($this->config->isOpenFilter()) {
                    $limit--;
                }
            }

            $idx++;
        }

        return $indexes;
    }

    private function isActiveFilter(AbstractFilter $filter): bool
    {
        $activeFilters = $this->layerResolver->get()->getState()->getFilters();

        foreach ($activeFilters as $item) {
            if ($item->getFilter()->getRequestVar() === $filter->getRequestVar()) {
                return true;
            }
        }

        return false;
    }
}
