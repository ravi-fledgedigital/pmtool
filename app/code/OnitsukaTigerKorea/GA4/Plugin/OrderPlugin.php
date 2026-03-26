<?php

namespace OnitsukaTigerKorea\GA4\Plugin;

use OnitsukaTigerKorea\GA4\Helper\Data as HelperData;
use WeltPixel\GA4\Block\Order;

class OrderPlugin
{
    /**
     * @param Order $subject
     * @param $result
     * @return array
     */
    public function afterGetProducts(Order $subject, $result): array
    {
        $i = 0;
        $order = $subject->getOrder();
        foreach ($order->getAllVisibleItems() as $item) {
            $result[$i]['originalPrice'] = HelperData::formatPrice($item->getOriginalPrice());
            $result[$i]['priceInclTax'] = HelperData::formatPrice($item->getPriceInclTax());
            $i++;
        }
        return $result;
    }

}
