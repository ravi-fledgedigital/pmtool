<?php

namespace Seoulwebdesign\Kakaopay\Model\Config\Source;

class PaymentMethodType implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'CARD',  'label' => 'CARD'],
            ['value' => 'MONEY', 'label' => 'MONEY']
        ];
    }
}
