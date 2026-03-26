<?php
namespace OnitsukaTiger\Customer\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Option\ArrayInterface;
use Magento\Customer\Model\ResourceModel\Group\Collection;

class PromotionGroup extends AbstractSource implements ArrayInterface
{
    /**
     * @var Collection
     */
    protected $customerGroup;

    /**
     * @var array
     */
    protected $options;

    /**
     * Group constructor.
     * @param Collection $customerGroup
     */
    public function __construct(
        Collection $customerGroup
    ) {
        $this->customerGroup = $customerGroup;
    }

    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        if (!$this->options) {
            $this->options = $this->customerGroup->toOptionArray();
        }
        return $this->options;
    }

    /**
     * @return array|void
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->customerGroup->toOptionArray();
        }
        return $this->options;
    }
}
