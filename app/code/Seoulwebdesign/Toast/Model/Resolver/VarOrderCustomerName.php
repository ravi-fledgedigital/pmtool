<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Seoulwebdesign\Toast\Model\Message;

class VarOrderCustomerName
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
            $billingAddress = $order->getBillingAddress();
            $customerName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
            return $customerName;
        } catch (\Throwable $t) {
            return null;
        }
    }
}
