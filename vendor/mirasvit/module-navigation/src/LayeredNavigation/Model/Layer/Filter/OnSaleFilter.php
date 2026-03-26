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

class OnSaleFilter extends AbstractFilter
{
    private $attributeCode = ExtraFilterConfigProvider::ON_SALE_FILTER;

    private $extraFilterConfigProvider;

    private $isApplied = false;

    public function __construct(
        ExtraFilterConfigProvider $extraFilterConfigProvider,
        Layer $layer,
        Context $context,
        array $data = []
    ) {
        parent::__construct($layer, $context, $data);

        $this->_requestVar               = ExtraFilterConfigProvider::ON_SALE_FILTER_FRONT_PARAM;
        $this->extraFilterConfigProvider = $extraFilterConfigProvider;
    }

    public function apply(RequestInterface $request): self
    {
        if (!$this->extraFilterConfigProvider->isOnSaleFilterEnabled()) {
            return $this;
        }

        $attributeValue = (string)$request->getParam($this->_requestVar);

        if (!$attributeValue && $attributeValue !== '0') {
            return $this;
        }

        $valueLabel = $attributeValue ? __('Yes') : __('No');

        $this->getProductCollection()->addFieldToFilter($this->attributeCode, $attributeValue);

        $this->addState((string)$valueLabel, $attributeValue);
        $this->isApplied = true;

        return $this;
    }

    public function getName(): string
    {
        $saleName = $this->extraFilterConfigProvider->getOnSaleFilterLabel();
        $saleName = ($saleName) ? : ExtraFilterConfigProvider::ON_SALE_FILTER_DEFAULT_LABEL;

        return $saleName;
    }

    protected function _getItemsData(): array
    {
        if (!$this->extraFilterConfigProvider->isOnSaleFilterEnabled()) {
            return [];
        }

        $optionsFacetedData = $this->getProductCollection()->getExtendedFacetedData($this->attributeCode);

        $optionsData = [
            [
                'label' => __('Yes'),
                'value' => 1,
                'count' => isset($optionsFacetedData[1]) ? $optionsFacetedData[1]['count'] : 0,
            ],
            [
                'label' => __('No'),
                'value' => 0,
                'count' => isset($optionsFacetedData[0]) ? $optionsFacetedData[0]['count'] : 0,
            ]
        ];

        if ($this->shouldDisplayFilter($optionsData)) {
            foreach ($optionsData as $data) {
                if ($data['count'] < 1 || $this->shouldSkipItem($data)) {
                    continue;
                }
    
                $this->itemDataBuilder->addItemData(
                    $data['label'],
                    $data['value'],
                    $data['count']
                );
            }
        }

        return $this->itemDataBuilder->build();
    }

    private function shouldSkipItem(array $data): bool
    {
        return $this->extraFilterConfigProvider->isOnSaleFilterHideNoOption()
            && (int)$data['value'] === 0;
    }

    private function addState(string $label, string $filter): void
    {
        $this->addStateItem($this->_createItem($label, $filter));
    }
}
