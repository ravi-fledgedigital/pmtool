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


namespace Mirasvit\LayeredNavigation\Block\Adminhtml\Group;


use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Swatches\Helper\Data as SwatchDataHelper;
use Magento\Swatches\Model\Swatch;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;

class OptionLabel
{
    private $swatchDataHelper;

    private $configProvider;

    public function __construct(
        SwatchDataHelper $swatchDataHelper,
        ConfigProvider $configProvider
    ) {
        $this->swatchDataHelper = $swatchDataHelper;
        $this->configProvider   = $configProvider;
    }

    public function getOptionLabelHtml(AttributeOptionInterface $option): string
    {
        return "<div class='mst-nav__option'>
                    <div class='mst-nav__option-swatch'>" . $option->getLabel() . "</div>"
                    . $this->getOptionSwatchHtml($option)
                . "</div>";
    }

    private function getOptionSwatchHtml(AttributeOptionInterface $option): string
    {
        $swatchData = $this->swatchDataHelper->getSwatchesByOptionsId([$option->getValue()]);

        if (empty($swatchData)) {
            return '';
        }

        $swatchType  = $swatchData[$option->getValue()]['type'];
        $swatchValue = $swatchData[$option->getValue()]['value'];

        switch ($swatchType) {
            case Swatch::SWATCH_TYPE_VISUAL_COLOR:
                $backgroundValue = $swatchValue;
                break;
            case Swatch::SWATCH_TYPE_VISUAL_IMAGE:
                $backgroundValue = 'url(' . $this->configProvider->getMediaUrl($swatchValue) . ') no-repeat center/100%';
                break;
            default:
                $backgroundValue = 'none';
                break;
        }

        $backgroud = ' background: ' . $backgroundValue;

        $html = "
            <div
                class='mst-nav__option-swatch option-swatch'
                style='" . $backgroud . "'
            ></div>
        ";

        return $html;
    }
}
