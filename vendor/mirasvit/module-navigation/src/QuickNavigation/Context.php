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

namespace Mirasvit\QuickNavigation;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\Layer\State;
use Magento\Store\Model\StoreManagerInterface;

class Context
{
    const SEQUENCE_FILTERS_LIMIT = 15;

    private $layerResolver;

    private $storeManager;

    public function __construct(
        LayerResolver $layerResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->layerResolver = $layerResolver;
        $this->storeManager  = $storeManager;
    }

    public function getStoreId(): int
    {
        return (int)$this->storeManager->getStore()->getId();
    }

    public function getCategoryId(): int
    {
        $category = $this->layerResolver->get()->getCurrentCategory();

        return $category
            ? (int)$category->getId()
            : 0;
    }

    public function getLayer(): Layer
    {
        return $this->layerResolver->get();
    }

    public function getState(): State
    {
        return $this->getLayer()->getState();
    }

    public function getSequenceString(): string
    {
        $filterList = [];
        $i = 0;
        foreach ($this->getState()->getFilters() as $filter) {
            foreach (explode(',', (string)$filter->getValueString()) as $value) {
                $filterList[] = $filter->getFilter()->getRequestVar() . ':' . $value;
                if (++$i >= self::SEQUENCE_FILTERS_LIMIT) {
                    break 2;
                }
            }
        }

        sort($filterList); // sort filters to avoid duplicates like color:50|color:51 and color:51|color:50

        return implode('|', $filterList);
    }


    public function getSequenceLength(): int
    {
        return count($this->getState()->getFilters());
    }
}
