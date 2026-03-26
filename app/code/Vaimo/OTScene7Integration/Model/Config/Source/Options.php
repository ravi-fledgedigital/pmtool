<?php
namespace Vaimo\OTScene7Integration\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Options extends AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (null === $this->_options) {
            $this->_options=[
                ['label' => __('NO'), 'value' => 1],
                ['label' => __('PRESCHOOL'), 'value' => 2],
                ['label' => __('TODDLER'), 'value' => 3]
            ];
        }
        return $this->_options;
    }
}
