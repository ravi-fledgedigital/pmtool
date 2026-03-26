<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Type implements ArrayInterface
{
    public const CUSTOM = 0;
    public const MAGENTO = 1;
    public const ANOTHER_MODULES = 2;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CUSTOM,
                'label' => __('Custom')
            ],
            [
                'value' => self::MAGENTO,
                'label' => __('Default')
            ],
            [
                'value' => self::ANOTHER_MODULES,
                'label' => __('3rd-party')
            ]
        ];
    }
}
