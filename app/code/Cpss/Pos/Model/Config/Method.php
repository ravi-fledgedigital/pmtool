<?php

namespace Cpss\Pos\Model\Config;

class Method implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'password', 'label' => __('Password')], ['value' => 'key', 'label' => __('Key')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['password' => __('Password'), 'key' => __('Key')];
    }
}
