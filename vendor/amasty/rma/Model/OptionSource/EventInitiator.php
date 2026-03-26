<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\OptionSource;

use Magento\Framework\Data\OptionSourceInterface;

class EventInitiator implements OptionSourceInterface
{
    public const CUSTOMER = 0;
    public const MANAGER = 1;
    public const SYSTEM = 2;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = [];
        foreach ($this->toArray() as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => $label];
        }
        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::CUSTOMER => __('Customer'),
            self::MANAGER => __('Manager'),
            self::SYSTEM => __('System')
        ];
    }
}
