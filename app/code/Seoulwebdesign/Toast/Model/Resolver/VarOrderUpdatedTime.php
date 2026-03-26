<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Seoulwebdesign\Toast\Model\Message;

class VarOrderUpdatedTime
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
            return date("Y년 m월 d일 ", strtotime($order->getUpdatedAt()));
        } catch (\Throwable $t) {
            return null;
        }
    }
}
