<?php

namespace WeltPixel\GA4\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class AddonOrderTotalCalculation
 *
 * @package WeltPixel\GA4\Model\Config\Source
 */
class AddonOrderTotalCalculation implements ArrayInterface
{

    const CALCULATE_DEFAULT = 'default';
    const CALCULATE_SUBTOTAL = 'subtotal';
    const CALCULATE_GRANDTOTAL = 'grandtotal';

    /**
     * Return list of Id Options
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::CALCULATE_DEFAULT,
                'label' => __('Default / Uses GA4 settings')
            ),
            array(
                'value' => self::CALCULATE_SUBTOTAL,
                'label' => __('Subtotal')
            ),
            array(
                'value' => self::CALCULATE_GRANDTOTAL,
                'label' => __('Grandtotal')
            )
        );
    }
}
