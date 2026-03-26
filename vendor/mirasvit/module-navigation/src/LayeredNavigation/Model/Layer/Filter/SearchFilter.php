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
use Magento\Framework\App\RequestInterface;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;

class SearchFilter extends AbstractFilter
{
    private $extraFilterConfigProvider;

    public function __construct(
        ExtraFilterConfigProvider $extraFilterConfigProvider,
        Layer $layer,
        Context $context,
        array $data = []
    ) {
        parent::__construct($layer, $context, $data);

        $this->_requestVar               = ExtraFilterConfigProvider::SEARCH_FILTER_FRONT_PARAM;
        $this->extraFilterConfigProvider = $extraFilterConfigProvider;
    }

    public function apply(RequestInterface $request): self
    {

        if (!$this->extraFilterConfigProvider->isSearchFilterEnabled()) {
            return $this;
        }

        $attributeValue = $request->getParam($this->_requestVar);

        if (empty($attributeValue)) {
            return $this;
        }

        $attributeValue = str_replace(',', ' ', $attributeValue);

        $this->getLayer()->getProductCollection()->addFieldToFilter($this->_requestVar, $attributeValue);

        return $this;
    }

    public function getName(): string
    {
        $searchName = $this->extraFilterConfigProvider->getSearchFilterLabel();
        $searchName = $searchName ? : ExtraFilterConfigProvider::SEARCH_FILTER_DEFAULT_LABEL;

        return $searchName;
    }

    protected function _getItemsData(): array
    {
        if (
            !$this->extraFilterConfigProvider->isSearchFilterEnabled()
            || (!$this->extraFilterConfigProvider->isSearchFilterFulltext() && !$this->extraFilterConfigProvider->isSearchFilterOptions())
        ) {
            return [];
        }

        // need this to render search filter
        $this->itemDataBuilder->addItemData(
            $this->getName(),
            ExtraFilterConfigProvider::SEARCH_FILTER,
            1
        );

        return $this->itemDataBuilder->build();
    }
}
