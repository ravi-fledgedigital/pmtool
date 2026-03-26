<?php

namespace Cpss\JsBarcode\Model\Config;

class Font implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'monospace',  'label' => __('Monospace')], 
            ['value' => 'sans-serif', 'label' => __('Sans-serif')],
            ['value' => 'serif', 'label' => __('Serif')],
            ['value' => 'fantasy', 'label' => __('Fantasy')],
            ['value' => 'cursive', 'label' => __('Cursive')]
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
            'monospace'       => __('Monospace'), 
            'sans-serif'      => __('Sans-serif'),
            'serif'      => __('Serif'),
            'fantasy'      => __('Fantasy'),
            'cursive'         => __('Cursive')
        ];
    }
}