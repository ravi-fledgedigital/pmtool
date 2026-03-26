<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Seoulwebdesign\Toast\Model\Message;

class VarCustomerName
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
            $customer = $data['customer'];
            return  $customer->getFirstname() . ' ' . $customer->getLastname();
        } catch (\Throwable $t) {
            return null;
        }
    }
}
