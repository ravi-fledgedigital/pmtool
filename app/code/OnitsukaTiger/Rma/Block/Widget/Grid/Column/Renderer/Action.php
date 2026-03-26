<?php

namespace OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer;

use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Backend\Block\Context;

class Action extends AbstractRenderer
{
    /**
     * @param DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $row->getData('request_id');
        $url = $this->getUrl('amrma/request/view',[ 'request_id' => $value ]);
        return '<a href="'. $this->escapeHtml($url).'" target="_blank" >'.__('View').'</a>';
    }
}
