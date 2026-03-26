<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\OptionSource;

use Magento\Framework\Option\ArrayInterface;

class State implements ArrayInterface
{
    public const PENDING = 0;
    public const AUTHORIZED = 1;
    public const RECEIVED = 2;
    public const RESOLVED = 3;
    public const CANCELED = 4;

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
            self::PENDING => __('Processing'),
            self::AUTHORIZED => __('Approved'),
            self::RECEIVED => __('Delivered'),
            self::RESOLVED => __('Completed'),
            self::CANCELED => __('Canceled')
        ];
    }
}
