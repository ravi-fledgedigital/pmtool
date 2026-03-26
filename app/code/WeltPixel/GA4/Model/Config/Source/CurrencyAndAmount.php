<?php

namespace WeltPixel\GA4\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Id
 *
 * @package WeltPixel\GA4\Model\Config\Source
 */
class CurrencyAndAmount implements ArrayInterface
{

    const STORE_SCOPE = 'store';
    const BASE_SCOPE = 'base';
    /**
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::STORE_SCOPE,
                'label' => __('Display Scope')
            ),
            array(
                'value' => self::BASE_SCOPE,
                'label' => __('Base Scope')
            )
        );
    }
}
