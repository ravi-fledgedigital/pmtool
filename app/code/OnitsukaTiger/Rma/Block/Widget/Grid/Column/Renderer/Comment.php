<?php

namespace OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\Text;

class Comment extends Text
{
    /**
     * @param DataObject $row
     * @return array|mixed|string|void|null
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        return "<div style='width: 400px'>".$this->escapeHtml($value)."</div>";
    }
}
