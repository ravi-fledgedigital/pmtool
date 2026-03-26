<?php
namespace OnitsukaTiger\Sorting\Model\Config\Source;

class Orderby implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        $result[] = [
            'value' => 'asc',
            'label' => __('Ascending')
        ];
        $result[] = [
            'value' => 'desc',
            'label' => __('Descending')
        ];
        return $result;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = [];
        $result['asc'] = __('Ascending');
        $result['desc'] = __('Descending');

        return $result;
    }
}
