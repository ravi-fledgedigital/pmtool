<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Seoulwebdesign\Toast\Model\Message;

class VarOrderPaymentMethod
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
            return $order->getPayment()->getMethodInstance()->getTitle();
        } catch (\Throwable $t) {
            return null;
        }
    }
}
