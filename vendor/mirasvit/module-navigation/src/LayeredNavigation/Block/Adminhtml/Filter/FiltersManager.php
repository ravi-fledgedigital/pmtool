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

namespace Mirasvit\LayeredNavigation\Block\Adminhtml\Filter;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Model\Config\HorizontalBarConfigProvider;

class FiltersManager extends Template
{
    protected $_template = 'Mirasvit_LayeredNavigation::filters_manager/index.phtml';

    private $collection;

    private $horizontalBarConfigProvider;

    public function __construct(
        AttributeCollection $collection,
        HorizontalBarConfigProvider $horizontalBarConfigProvider,
        Template\Context $context
    ) {
        $this->collection = $collection;
        $this->horizontalBarConfigProvider = $horizontalBarConfigProvider;
        parent::__construct($context);
    }

    public function getAttributes(string $position): array
    {
        $horizontalFilters = $this->horizontalBarConfigProvider->getFilters();

        $attributeCollection = $this->collection
            ->addVisibleFilter()
            ->addFieldToFilter('is_filterable', ['neq' => 0])
            ->setOrder('position', 'ASC');

        $attributes = [];

        foreach ($attributeCollection as $attribute) {
            $attrCode = $attribute->getAttributeCode();

            if (isset($horizontalFilters[$attrCode]) && $horizontalFilters[$attrCode][AttributeConfigInterface::POSITION] === HorizontalBarConfigProvider::POSITION_HORIZONTAL) {
                $this->addFilter($attribute, HorizontalBarConfigProvider::POSITION_HORIZONTAL, $attributes, $horizontalFilters[$attrCode]);
            } else if (isset($horizontalFilters[$attrCode]) && $horizontalFilters[$attrCode][AttributeConfigInterface::POSITION] === HorizontalBarConfigProvider::POSITION_BOTH) {
                $this->addFilter($attribute, HorizontalBarConfigProvider::POSITION_HORIZONTAL, $attributes, $horizontalFilters[$attrCode]);
                $this->addFilter($attribute, HorizontalBarConfigProvider::POSITION_SIDEBAR, $attributes, $horizontalFilters[$attrCode]);
            } else {
                $this->addFilter($attribute, HorizontalBarConfigProvider::POSITION_SIDEBAR, $attributes, []);
            }
        }

        if (!empty($attributes[$position])) {
            uasort($attributes[$position], function ($a, $b) {
                return $a['horizontal_position'] <=> $b['horizontal_position'];
            });
        }
        return $attributes[$position] ?? [];
    }


    private function addFilter($attribute, $filterType, &$attributes, $horizontalFilter): array
    {
        $horizontalPosition = null;

        if ($filterType === HorizontalBarConfigProvider::POSITION_HORIZONTAL) {
            $horizontalPosition = isset($horizontalFilter[AttributeConfigInterface::HORIZONTAL_POSITION])
                ? (int)$horizontalFilter[AttributeConfigInterface::HORIZONTAL_POSITION]
                : 0;
        }

        $attributes[$filterType][$attribute->getAttributeCode()] = [
            'id' => $attribute->getId(),
            'code' => $attribute->getAttributeCode(),
            'label' => $attribute->getFrontendLabel(),
            'horizontal_position' => $horizontalPosition,
        ];

        return $attributes;
    }

    public function renderFilterListHtml(string $position): string
    {
        $filters = $this->getAttributes($position) ?? [];

        $block = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Template::class);
        $block->setTemplate('Mirasvit_LayeredNavigation::filters_manager/filter/list.phtml');
        $block->setData('filters', $filters);
        $block->setData('position', $position);

        return $block->toHtml();
    }
}
