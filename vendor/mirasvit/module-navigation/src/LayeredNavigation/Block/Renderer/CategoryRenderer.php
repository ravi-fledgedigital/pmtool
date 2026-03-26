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

use Magento\Framework\View\Element\Template;
use Magento\Swatches\Helper\Media as MediaHelper;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\HighlightConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\SeoConfigProvider;
use Mirasvit\LayeredNavigation\Model\Config\Source\FilterItemDisplayModeSource;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;
use Mirasvit\LayeredNavigation\Service\FilterService;

class CategoryRenderer extends LabelRenderer
{
    protected $_template = 'Mirasvit_LayeredNavigation::renderer/categoryRenderer.phtml';

    private $extraFilterConfigProvider;

    private $context;

    public function __construct(
        ExtraFilterConfigProvider $extraFilterConfigProvider,
        FilterService $filterService,
        ConfigProvider $configProvider,
        HighlightConfigProvider $highlightConfigProvider,
        MediaHelper $mediaHelper,
        SeoConfigProvider $seoConfigProvider,
        Template\Context $context,
        array $data = []
    ) {
        $this->extraFilterConfigProvider = $extraFilterConfigProvider;
        $this->context                   = $context;

        parent::__construct(
            $filterService,
            $configProvider,
            $highlightConfigProvider,
            $mediaHelper,
            $seoConfigProvider,
            $context,
            $data
        );
    }

    public function isCategoriesCollapsible(): bool
    {
        return $this->extraFilterConfigProvider->isCategoriesCollapsible() && $this->getMaxLevel() > 0;
    }

    private function getMaxLevel(): int
    {
        $level = 0;

        foreach ($this->getFilterItems() as $filterItem) {
            if ($filterItem->getData('level') && $filterItem->getData('level') > $level) {
                $level = $filterItem->getData('level');
            }
        }

        return (int)$level;
    }

    public function isCategoryFilterModeLink(): bool
    {
        $isCategoryPage = $this->context->getRequest()->getFullActionName() === 'catalog_category_view';

        return $isCategoryPage && $this->extraFilterConfigProvider->isCategoryFilterModeLink();
    }

    public function getFilterItemDisplayMode(string $attributeCode = ''): string
    {
        return $this->isCategoryFilterModeLink() 
            ? FilterItemDisplayModeSource::OPTION_LINK
            : $this->configProvider->getFilterItemDisplayMode($attributeCode);
    }
}
