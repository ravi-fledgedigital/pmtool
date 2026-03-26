<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Config\Source;

use Magento\Customer\Model\Group;

class CustomerGroup implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Customer\Model\Customer\Attribute\Source\Group
     */
    private $groupSource;

    public function __construct(\Magento\Customer\Model\Customer\Attribute\Source\Group $groupSource)
    {
        $this->groupSource = $groupSource;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array_merge(
            [['value' => Group::NOT_LOGGED_IN_ID, 'label' => __('NOT LOGGED IN')]],
            $this->groupSource->getAllOptions()
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $optionArray = $this->toOptionArray();
        $labels =  array_column($optionArray, 'label');
        $values =  array_column($optionArray, 'value');
        return array_combine($values, $labels);
    }
}
