<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Order\Plugin;

class OrderView
{
    /**
     * @param \Magento\Sales\Block\Order\Info $subject
     * @param                                 $result
     *
     * @return string
     */
    public function afterToHtml(\Magento\Sales\Block\Order\Info $subject, $result)
    {
        /** @var \OnitsukaTiger\OrderAttribute\Block\Order\Attributes $attributesBlock */
        if ($attributesBlock = $subject->getChildBlock('order_attributes')) {
            $result .= $attributesBlock->toHtml();
        }

        return $result;
    }
}
