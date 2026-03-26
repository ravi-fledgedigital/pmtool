<?php

namespace OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer;

use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Backend\Block\Context;

class OrderAction extends AbstractRenderer
{
    /**
     * @param DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $url = $this->getUrl('sales/order/view', ['order_id' => $row->getParentId()]);
        return '<a href="'. $this->escapeHtml($url).'" target="_blank" >'.__('View').'</a>';
    }
}
