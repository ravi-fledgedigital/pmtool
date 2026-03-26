<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\Relation;

use OnitsukaTiger\OrderAttribute\Controller\RegistryConstants;

class DependentAttributeProvider implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var null|array
     */
    protected $options = null;

    /**
     * @var null|int
     */
    protected $parentAttributeId = null;

    /**
     * @var null|int[]
     */
    protected $excludedAttributeIds = null;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var ParentAttributeProvider
     */
    private $attributeProvider;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Attribute\Repository
     */
    private $repository;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails\CollectionFactory
     */
    private $relationCollectionFactory;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\CollectionFactory
     */
    private $collectionFactory;

    /**
     * DependentAttributeProvider constructor.
     * @param \Magento\Framework\Registry $coreRegistry
     * @param ParentAttributeProvider $attributeProvider
     * @param \OnitsukaTiger\OrderAttribute\Model\Attribute\Repository $repository
     * @param \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails\CollectionFactory $relationCollectionFactory
     * @param \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        ParentAttributeProvider $attributeProvider,
        \OnitsukaTiger\OrderAttribute\Model\Attribute\Repository $repository,
        \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails\CollectionFactory $relationCollectionFactory,
        \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\CollectionFactory $collectionFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->attributeProvider = $attributeProvider;
        $this->repository = $repository;
        $this->relationCollectionFactory = $relationCollectionFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [];
            if (!$this->getParentAttributeId()) {
                return $this->options;
            }
            $parentAttribute = $this->repository->getById($this->getParentAttributeId());

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('is_user_defined', 1);
            $collection->setOrder('sorting_order', 'ASC');
            $collection->addFieldToFilter('main_table.attribute_id', ['nin' => $this->getExcludedIds()]);
            $collection->addFieldToFilter('additional_table.checkout_step', $parentAttribute->getCheckoutStep());

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
     * Get Parent Attribute ID
     * Dependent attribute should not be like parent attribute
     *
     * @return int|false
     */
    protected function getParentAttributeId()
    {
        if ($this->parentAttributeId === null) {
            /** @var Relation $relation */
            $relation = $this->coreRegistry->registry(RegistryConstants::CURRENT_RELATION_ID);
            if ($relation instanceof Relation && $relation->getAttributeId()) {
                $this->parentAttributeId = $relation->getAttributeId();
            } else {
                $this->parentAttributeId = false;
                // If relation new then take first attribute from dropdown "Parent Attribute"
                $attribute = $this->attributeProvider->getDefaultSelected();
                if ($attribute) {
                    $this->parentAttributeId = $attribute['value'];
                }
            }
        }
        return $this->parentAttributeId;
    }

    /**
     * Return Excluded Attribute IDs which can't be as Dependent attribute for this relation.
     * Exclude attributes which already have relations as parent for avoid loop
     *
     * @return int[]|null
     */
    protected function getExcludedIds()
    {
        if ($this->excludedAttributeIds === null) {
            $parentId = $this->getParentAttributeId();
            /** @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails\Collection $collection */
            $collection = $this->relationCollectionFactory->create();
            $collection->addFieldToFilter('dependent_attribute_id', $parentId);
            $this->excludedAttributeIds = array_unique($collection->getColumnValues('attribute_id'));
            $this->excludedAttributeIds[] = $parentId;
        }

        return $this->excludedAttributeIds;
    }

    /**
     * Force set attribute ID
     *
     * @param int $parentAttributeId
     *
     * @return $this
     */
    public function setParentAttributeId($parentAttributeId)
    {
        $this->parentAttributeId = $parentAttributeId;
        return $this;
    }
}
