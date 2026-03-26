<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Order\Plugin;

class OrderView
{
    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\View\Info $subject
     * @param string                                         $result
     *
     * @return string
     */
    public function afterToHtml(
        \Magento\Sales\Block\Adminhtml\Order\View\Info $subject,
        $result
    ) {
        $attributesBlock = $subject->getChildBlock('order_attributes');
        if ($attributesBlock) {
            $attributesBlock->setTemplate("OnitsukaTiger_OrderAttribute::order/view/attributes.phtml");
            $result = $result . $attributesBlock->toHtml();
        }

        return $result;
    }
}
