<?php

namespace OnitsukaTiger\PreOrders\Ui\Component\Listing\Order\Column;

class Options implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => 1,
                'label' => __('Yes')
            ],
            [
                'value' => 0,
                'label' => __('No')
            ]
        ];

        return $options;
    }
}