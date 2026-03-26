<?php
namespace OnitsukaTiger\CategorySort\Plugin\Catalog\Model;

/**
 * Class Config
 * @package OnitsukaTiger\CategorySort\Plugin\Catalog\Model
 */
class Config
{
    /**
     * @param \Magento\Catalog\Model\Config $subject
     * @param $options
     * @return array
     */
    public function afterGetAttributeUsedForSortByArray(\Magento\Catalog\Model\Config $subject, $options)
    {
        unset($options['position']);
        $result = [];
        $result['popularity'] = __('Popularity');
        $result['price_high_to_low'] = __('Price High to Low');
        $result['price_low_to_high'] = __('Price Low to High');

        return array_merge($result, $options);
    }
}
