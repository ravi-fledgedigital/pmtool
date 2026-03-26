<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\Relation;

use OnitsukaTiger\OrderAttribute\Api\Data\RelationInterface;
use OnitsukaTiger\OrderAttribute\Api\Data\RelationDetailInterface;

class Relation extends \Magento\Framework\Model\AbstractModel implements RelationInterface
{
    /**
     * @var RelationDetailsFactory
     */
    private $detailsFactory;

    /**
     * @var bool
     */
    protected $datailsChanged = false;

    /**
     * Relation constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation $resource
     * @param \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation\Collection $resourceCollection
     * @param RelationDetailsFactory $detailsFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation $resource,
        \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation\Collection $resourceCollection,
        \OnitsukaTiger\OrderAttribute\Model\Attribute\Relation\RelationDetailsFactory $detailsFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->detailsFactory = $detailsFactory;
    }

    public function _construct()
    {
        $this->_init(\OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation::class);
    }

    /**
     * Initialize relation model data from array.
     *
     * @param array $data
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadPost(array $data)
    {
        if (!isset($data['name'])
            || !isset($data['attribute_id'])
            || !isset($data['attribute_options'])
            || !isset($data['dependent_attributes'])
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Data is incorrect.'));
        }
        $this->setName($data['name']);

        $details = [];
        foreach ($data['attribute_options'] as $option) {
            foreach ($data['dependent_attributes'] as $attribute) {
                $details[] = $this->detailsFactory->create()
                    ->setAttributeId($data['attribute_id'])
                    ->setOptionId($option)
                    ->setDependentAttributeId($attribute);
            }
        }
        $this->setDetails($details);

        return $this;
    }

    /**
     * load and set Relation Details data
     *
     * @return $this
     */
    public function loadRelationDetails()
    {
        $this->getAttributeId();
        $this->getAttributeOptions();
        $this->getDependentAttributes();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationId()
    {
        return $this->_getData(self::RELATION_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setRelationId($relationId)
    {
        $this->setData(self::RELATION_ID, $relationId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->_getData(self::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->setData(self::NAME, $name);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDetails()
    {
        if ($this->getRelationId() && $this->_getData('relation_details') === null) {
            $this->setDetails($this->getResource()->getDetails($this->getRelationId()));
        }

        $details = $this->_getData('relation_details');
        return is_array($details) ? $details : [];
    }

    /**
     * {@inheritdoc}
     */
    public function setDetails($relationDetails)
    {
        $this->setData('relation_details', $relationDetails);
        return $this;
    }

    /**
     * @return int
     */
    public function getAttributeId()
    {
        if ($this->_getData('attribute_id') === null) {
            foreach ($this->getDetails() as $relationDetail) {
                $this->setData('attribute_id', $relationDetail->getAttributeId());
                break;
            }
        }

        return $this->_getData('attribute_id');
    }

    /**
     * @return string
     */
    public function getAttributeOptions()
    {
        if ($this->_getData('attribute_options') === null) {
            $this->setData(
                'attribute_options',
                join(',', $this->getDetailColumnValues(RelationDetailInterface::OPTION_ID))
            );
        }

        return $this->_getData('attribute_options');
    }

    /**
     * @return string
     */
    public function getDependentAttributes()
    {
        if ($this->_getData('dependent_attributes') === null) {
            $this->setData(
                'dependent_attributes',
                join(',', $this->getDetailColumnValues(RelationDetailInterface::DEPENDENT_ATTRIBUTE_ID))
            );
        }

        return $this->_getData('dependent_attributes');
    }

    /**
     * @param string $column
     *
     * @return array
     */
    protected function getDetailColumnValues($column)
    {
        $options = [];
        foreach ($this->getDetails() as $relationDetail) {
            $options[$relationDetail->getRelationDetailId()] = $relationDetail->getData($column);
        }
        return $options;
    }
}
