<?php
namespace WeltPixel\GA4\Model\Config\Source\ServerSide;

use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Framework\Option\ArrayInterface;

class CustomerGroups implements ArrayInterface
{
    /**
     * @var GroupCollection
     */
    protected $groupCollection;

    /**
     * @param GroupCollection $groupCollection
     */
    public function __construct(GroupCollection $groupCollection)
    {
        $this->groupCollection = $groupCollection;
    }

    /**
     * Return array of customer groups as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $groups = $this->groupCollection->toOptionArray();
        return $groups;
    }
}
