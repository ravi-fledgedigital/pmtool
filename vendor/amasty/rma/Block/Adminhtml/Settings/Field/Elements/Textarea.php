<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Settings\Field\Elements;

class Textarea extends \Magento\Framework\View\Element\AbstractBlock
{
    public function toHtml()
    {
        return '<textarea id="' . $this->getInputId() .
            '"' .
            ' name="' .
            $this->getInputName() .
            '"><%- ' . $this->getColumnName() .' %></textarea>';
    }
}
