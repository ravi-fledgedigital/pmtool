<?php

namespace Seoulwebdesign\Toast\Model\MessageField;

use Seoulwebdesign\Toast\Model\Message;
use Seoulwebdesign\Toast\Model\MessageFieldAbstract;

class Order extends MessageFieldAbstract
{
    /**
     * @var Message
     */
    protected $messageToast;

    /**
     * @param Message $messageToast
     */
    public function __construct(
        Message $messageToast
    ) {
        $this->messageToast = $messageToast;
    }

    /**
     * Get Available Variables
     *
     * @return string[][]
     */
    public function getAvailableVariables()
    {
        return [
            ['id' => 'var_order_id', 'label' => 'Order ID variable name'],
            ['id' => 'var_order_customer_name', 'label' => 'Order Customer Name variable name'],
            ['id' => 'var_order_total', 'label' => 'Order Total variable name'],
            ['id' => 'var_order_refunded', 'label' => 'Order Refund variable name'],
            ['id' => 'var_order_payment_method', 'label' => 'Order Payment Method variable name'],
            ['id' => 'var_order_products', 'label' => 'Order Products variable name'],
            [   'id' => 'var_order_products_name_extra',
                'label' => 'Products name attr extra (Ex: size, color)',
                'comment'=>'Ex: size, color'
            ],
            ['id' => 'var_order_tracking_code', 'label' => 'Order Tracking Code variable name'],
            ['id' => 'var_order_courier', 'label' => 'Order Courier variable name'],
            ['id' => 'var_order_created_time', 'label' => 'Order Created Time variable name'],
            ['id' => 'var_order_updated_time', 'label' => 'Order Updated Time variable name'],
        ];
    }

    /**
     * Get Ref Field List
     *
     * @return array
     */
    public function getRefFieldList()
    {
        return $this->messageToast->getOrderStatusKey();
    }
}
