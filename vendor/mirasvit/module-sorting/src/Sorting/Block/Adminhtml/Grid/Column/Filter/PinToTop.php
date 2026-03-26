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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Block\Adminhtml\Grid\Column\Filter;

use Magento\Backend\Block\Widget\Grid\Column\Filter\Select;

/**
 * Filter for "Pin to top" column with auto-submit on change.
 */
class PinToTop extends Select
{
    protected function _getOptions(): array
    {
        return [
            ['label' => __('Any'), 'value' => ''],
            ['label' => __('Yes'), 'value' => 1],
            ['label' => __('No'), 'value' => 0],
        ];
    }

    public function getHtml(): string
    {
        $gridJsObject = $this->getColumn()->getGrid()->getJsObjectName();
        $name         = $this->_getHtmlName();

        $html = '<select name="' . $name . '" id="' . $this->_getHtmlId() . '"' . $this->getUiId('filter', $name)
            . ' class="no-changes admin__control-select"'
            . ' onchange="' . $gridJsObject . '.doFilter()">';

        $value = $this->getValue();

        foreach ($this->_getOptions() as $option) {
            $html .= $this->_renderOption($option, $value);
        }

        $html .= '</select>';

        return $html;
    }
}
