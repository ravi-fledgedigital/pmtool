<?php

namespace Cpss\JsBarcode\Model\Config;

class Format implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'CODE128',  'label' => __('CODE128 auto')], 
            ['value' => 'CODE128A', 'label' => __('CODE128 A')],
            ['value' => 'CODE128B', 'label' => __('CODE128 B')],
            ['value' => 'CODE128C', 'label' => __('CODE128 C')],
            ['value' => 'EAN13', 'label' => __('EAN13')],
            ['value' => 'EAN8', 'label' => __('EAN8')],
            ['value' => 'UPC', 'label' => __('UPC')],
            ['value' => 'CODE39', 'label' => __('CODE39')],
            ['value' => 'ITF14', 'label' => __('ITF14')],
            ['value' => 'ITF', 'label' => __('ITF')],
            ['value' => 'MSI', 'label' => __('MSI')],
            ['value' => 'MSI10', 'label' => __('MSI10')],
            ['value' => 'MSI11', 'label' => __('MSI11')],
            ['value' => 'MSI1010', 'label' => __('MSI1010')],
            ['value' => 'MSI1110',  'label' => __('MSI1110')],
            ['value' => 'pharmacode', 'label' => __('pharmacode')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'CODE128'       => __('CODE128 auto'), 
            'CODE128A'      => __('CODE128 A'),
            'CODE128B'      => __('CODE128 B'),
            'CODE128C'      => __('CODE128 C'),
            'EAN13'         => __('EAN13'),
            'EAN8'          => __('EAN8'),
            'UPC'           => __('UPC'),
            'CODE39'        => __('CODE39'),
            'ITF14'         => __('ITF14'),
            'ITF'           => __('ITF'),
            'MSI'           => __('MSI'),
            'MSI10'         => __('MSI10'),
            'MSI11'         => __('MSI11'),
            'MSI1010'       => __('MSI1010'),
            'MSI1110'       => __('MSI1110'),
            'pharmacode'    => __('pharmacode')
        ];
    }
}