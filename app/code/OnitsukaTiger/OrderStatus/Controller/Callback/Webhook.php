<?php

namespace OnitsukaTiger\OrderStatus\Controller\Callback;

/**
 * Class Webhook
 * @package OnitsukaTiger\OrderStatus\Controller\Callback
 */
class Webhook extends \Omise\Payment\Controller\Callback\Webhook
{

    public function execute()
    {
        //the webhook handler is sent at the same time
        sleep(3);
        return parent::execute();
    }
}
