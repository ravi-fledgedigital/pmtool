<?php

namespace OnitsukaTiger\Newsletter\Model\Config\Source;

/**
 * @api
 * @since 100.0.2
 */
class Coupon implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'alphanum', 'label' => __('Alphanumeric')],
            ['value' => 'alpha', 'label' => __('Alphabetical')],
            ['value' => 'num', 'label' => __('Numeric')]
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
            'alphanum' => __('Alphanumeric'),
            'alpha' => __('Alphabetical'),
            'num' => __('Numeric')
        ];
    }
}
