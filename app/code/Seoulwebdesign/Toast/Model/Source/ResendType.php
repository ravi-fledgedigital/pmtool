<?php
namespace Seoulwebdesign\Toast\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ResendType implements OptionSourceInterface
{

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => 'SMS', 'value' => 'SMS'];
        $options[] = ['label' => 'LMS', 'value' => 'LMS'];
        return $options;
    }
}
