<?php
namespace OnitsukaTiger\Restock\Ui\Component\Listing\Column\RestockNotified;

use Magento\Framework\Data\OptionSourceInterface;

class Options implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('NOT YET')
            ],
            [
                'value' => 1,
                'label' => __('DONE')
            ]
        ];
    }
}