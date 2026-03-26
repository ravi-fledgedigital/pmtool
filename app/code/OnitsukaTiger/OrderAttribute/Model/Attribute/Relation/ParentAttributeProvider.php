<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\Relation;

use OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class ParentAttributeProvider implements OptionSourceInterface
{
    /**
     * @var null|array
     */
    protected $options = null;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var InputTypeProvider
     */
    private $inputTypeProvider;

    public function __construct(
        CollectionFactory $collectionFactory,
        InputTypeProvider $inputTypeProvider
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->inputTypeProvider = $inputTypeProvider;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [];

            /* attributes only with options */
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('is_user_defined', 1);
            $collection->setOrder('sorting_order', 'ASC');
            $collection->addFieldToFilter('frontend_input', $this->inputTypeProvider->getInputTypesWithOptions());

            foreach ($collection as $attribute) {
                $label = $attribute->getFrontendLabel();
                if (!$attribute->getIsVisibleOnFront()) {
                    $label .= ' - ' . __('Not Visible');
                }
                $this->options[] = [
                    'value' => $attribute->getAttributeId(),
                    'label' => $label
                ];
            }
        }

        return $this->options;
    }

    /**
     * Get selected Attribute ID for default
     * used when no Attribute ID in data for load Attribute options
     *
     * @return array|false
     */
    public function getDefaultSelected()
    {
        if (count($this->toOptionArray())) {
            return current($this->toOptionArray());
        }

        return false;
    }
}
