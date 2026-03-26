<?php

namespace OnitsukaTigerKorea\OrderCancel\Model\OptionSource;

use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    public const DISABLED = 0;
    public const ENABLED = 1;

    /**
     * @return array
     */
    public function toOptionArray(): array
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
    public function toArray(): array
    {
        return [
            self::ENABLED => __('Enabled'),
            self::DISABLED => __('Disabled'),
        ];
    }
}
