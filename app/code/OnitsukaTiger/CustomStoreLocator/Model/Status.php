<?php

namespace OnitsukaTiger\CustomStoreLocator\Model;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface{
    public function getOptionArray()
    {
        $option = ['1' => __('Enable'), '2' => __('Disable')];
        return $option;
    }

    public function getAllOption()
    {
        $res = $this->getOptions();
        array_unshift($res, ['value' => '', 'label' => '']);
        return $res;
    }

    public function getOptions()
    {
        $res = [];
        foreach ($this->getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    public function toOptionArray()
    {
        return $this->getOptions();
    }
}