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

namespace Mirasvit\LayeredNavigation\Block\Renderer;

use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Model\Config\SeoConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\Source\SizeLimiterDisplayModeSource;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;

abstract class AbstractRenderer extends Template
{
    /** @var FilterInterface */
    protected $filter;

    /** @var AttributeConfigInterface */
    protected $attributeConfig;

    protected $seoConfigProvider;

    protected $storeId;

    protected $configProvider;

    public function __construct(
        SeoConfigProvider $seoConfigProvider,
        ConfigProvider    $configProvider,
        Template\Context  $context,
        array             $data = []
    ) {
        $this->storeId           = (int)$context->getStoreManager()->getStore()->getId();
        $this->seoConfigProvider = $seoConfigProvider;
        $this->configProvider    = $configProvider;

        parent::__construct($context, $data);
    }

    public function setFilterData(FilterInterface $filter, AttributeConfigInterface $attributeConfig): self
    {
        $this->filter          = $filter;
        $this->attributeConfig = $attributeConfig;

        return $this;
    }

    public function getFilter(): FilterInterface
    {
        return $this->filter;
    }

    /** @return Item[] */
    public function getFilterItems(): array
    {
        return $this->filter->getItems();
    }

    public function getAttributeCode(): string
    {
        return (string)$this->filter->getRequestVar();
    }

    public function getItemId(Item $filterItem): string
    {
        return 'm_' . $this->getFilter()->getRequestVar() . '[' . $filterItem->getValueString() . ']';
    }

    public function getRelAttributeValue(): string
    {
        return $this->seoConfigProvider->getRelAttribute();
    }

    public function getCountElement(Item $filterItem): string
    {
        /** @var Template $block */
        $block = $this->_layout->createBlock(Template::class);
        $block->setTemplate('Mirasvit_LayeredNavigation::renderer/element/count.phtml')
            ->setData('count', $filterItem->getData('count'));

        return $block->toHtml();
    }

    public function getSizeLimiterElement(string $filterAccessor): string
    {
        /** @var Element\SizeLimiterElement $block */
        $block = $this->_layout->createBlock(Element\SizeLimiterElement::class);
        $block->setFilter($this->filter)
            ->setFilterAccessor($filterAccessor)
            ->setTemplate('Mirasvit_LayeredNavigation::renderer/element/sizeLimiter.phtml');

        return $block->toHtml();
    }

    public function getSearchBoxElement(string $filterAccessor): string
    {
        /** @var Element\SearchBoxElement $block */
        $block = $this->_layout->createBlock(Element\SearchBoxElement::class);
        $block->setFilter($this->filter)
            ->setAttributeConfig($this->attributeConfig)
            ->setFilterAccessor($filterAccessor)
            ->setTemplate('Mirasvit_LayeredNavigation::renderer/element/searchBox.phtml');

        return $block->toHtml();
    }

    public function getTooltip(): string
    {
        return $this->attributeConfig ? $this->attributeConfig->getTooltip() : '';
    }

    public function getAttributeClearUrl($attributeCode)
    {
        return $this->getUrl('*/*/*', [
                '_current' => true,
                '_use_rewrite' => true,
                '_query' => [$attributeCode => null],
                '_escape' => true,
            ]
        );
    }

    public function getFirstLetter(string $label): string
    {
        $letter = strtoupper(mb_substr($label, 0, 1));
        // compatibility with Hebrew
        return preg_match('/^[\p{Latin}\p{Cyrillic}\x{0590}-\x{05FF}]+$/u', $letter) ? $letter : '#';
    }

    public function isMultiselectEnabled(): bool
    {
        $isMultiselect = $this->configProvider->isMultiselectEnabled();

        return is_null($isMultiselect) ? $this->configProvider->isMultiselectEnabled() : $isMultiselect;
    }
}
