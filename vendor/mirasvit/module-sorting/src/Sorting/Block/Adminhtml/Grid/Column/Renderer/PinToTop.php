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

namespace Mirasvit\Sorting\Block\Adminhtml\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Checkbox;
use Magento\Framework\DataObject;

/**
 * Renderer for "Pin to top" checkbox column.
 */
class PinToTop extends Checkbox
{
    public function render(DataObject $row): string
    {
        $productId        = $row->getData('entity_id');
        $pinnedProductIds = $this->getColumn()->getValues();

        $checked = (is_array($pinnedProductIds) && in_array($productId, $pinnedProductIds))
            ? ' checked="checked"'
            : '';

        $productId = (int)$productId;

        return <<<HTML
<div class="data-grid-checkbox-cell-inner">
    <input type="checkbox" name="pin_to_top" class="checkbox admin__control-checkbox"
           value="$productId" id="pin_to_top_$productId" $checked />
    <label for="pin_to_top_$productId"></label>
</div>
HTML;
    }
}
