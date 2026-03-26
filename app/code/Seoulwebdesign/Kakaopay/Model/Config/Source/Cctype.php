<?php

namespace Seoulwebdesign\Kakaopay\Model\Config\Source;

class Cctype implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'SHINHAN', 'label' => 'SHINHAN'],
            ['value' => 'KB', 'label' => 'KB'],
            ['value' => 'HYUNDAI', 'label' => 'HYUNDAI'],
            ['value' => 'LOTTE', 'label' => 'LOTTE'],
            ['value' => 'SAMSUNG', 'label' => 'SAMSUNG'],
            ['value' => 'NH', 'label' => 'NH'],
            ['value' => 'BC', 'label' => 'BC'],
            ['value' => 'HANA', 'label' => 'HANA'],
            ['value' => 'CITI', 'label' => 'CITI'],
            ['value' => 'KAKAOBANK', 'label' => 'KAKAOBANK'],
            ['value' => 'KAKAOPAY', 'label' => 'KAKAOPAY'],
            ['value' => 'WOORI', 'label' => 'WOORI'],
            ['value' => 'GWANGJU', 'label' => 'GWANGJU'],
            ['value' => 'SUHYUP', 'label' => 'SUHYUP'],
            ['value' => 'SHINHYUP', 'label' => 'SHINHYUP'],
            ['value' => 'JEONBUK', 'label' => 'JEONBUK'],
            ['value' => 'JEJU', 'label' => 'JEJU'],
            ['value' => 'SC', 'label' => 'SC']
        ];
    }
}
