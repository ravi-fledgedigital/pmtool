<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Shipping\Block\Adminhtml\Items\Renderer;

class DefaultRenderer extends \Magento\Sales\Block\Adminhtml\Items\Renderer\DefaultRenderer {

    public function getCancelXmlSynced($order){
        return $order->getCancelXmlSynced();
    }
}
