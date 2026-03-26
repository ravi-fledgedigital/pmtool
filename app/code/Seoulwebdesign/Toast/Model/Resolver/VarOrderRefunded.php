<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Seoulwebdesign\Toast\Model\Message;

class VarOrderRefunded
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
                $orderRefunded = $currencySymbol . number_format($order->getTotalRefunded(), 0, '.', ',');
            } else {
                $orderRefunded = $currencySymbol . number_format($order->getTotalRefunded(), 2, ',', '.');
            }
            return $orderRefunded;
        } catch (\Throwable $t) {
            return null;
        }
    }
}
