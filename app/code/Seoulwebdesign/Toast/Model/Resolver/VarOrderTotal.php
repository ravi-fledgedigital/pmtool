<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Seoulwebdesign\Toast\Model\Message;

class VarOrderTotal
{
    /**
     * Main execute
     *
     * @param Message $message
     * @param array $data
     * @return string|null
     */
    public function execute($message, $data)
    {
        try {
            $order = $data['order'];
            $currencySymbol = $order->getOrderCurrency()->getCurrencySymbol();
            if ($order->getOrderCurrency()->getCurrencyCode()=='KRW') {
                $orderTotal = $currencySymbol . number_format($order->getGrandTotal(), 0, '.', ',');
            } else {
                $orderTotal =$currencySymbol . number_format($order->getGrandTotal(), 2, ',', '.');
            }
            return $orderTotal;
        } catch (\Throwable $t) {
            return null;
        }
    }
}
