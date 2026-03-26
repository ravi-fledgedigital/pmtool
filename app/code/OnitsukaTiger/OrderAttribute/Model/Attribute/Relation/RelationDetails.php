<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\Relation;

use OnitsukaTiger\OrderAttribute\Api\Data\RelationDetailInterface;

class RelationDetails extends \Magento\Framework\Model\AbstractModel implements RelationDetailInterface
{
    public function _construct()
    {
        $this->_init(\OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails::class);
    }

    /**
     * Returns EAV Attribute ID
     *
     * @return int
     */
    public function getAttributeId()
    {
        return $this->_getData(self::ATTRIBUTE_ID);
    }

    /**
     * @param int $attributeId
     *
     * @return $this
     */
    public function setAttributeId($attributeId)
    {
        $this->setData(self::ATTRIBUTE_ID, $attributeId);

        return $this;
    }

    /**
     * Returns Relation Detail Id
     *
     * @return int
     */
    public function getRelationDetailId()
    {
        return $this->_getData(self::RELATION_DETAIL_ID);
    }

    /**
     * @param int $relationDetailId
     *
     * @return $this
     */
    public function setDetailIdId($relationDetailId)
    {
        $this->setData(self::RELATION_DETAIL_ID, $relationDetailId);

        return $this;
    }

    /**
     * Returns Attribute Option ID
     *
     * @return int
     */
    public function getOptionId()
    {
        return $this->_getData(self::OPTION_ID);
    }

    /**
     * @param int $optionId
     *
     * @return $this
     */
    public function setOptionId($optionId)
    {
        $this->setData(self::OPTION_ID, $optionId);

        return $this;
    }

    /**
     * Returns Dependent EAD Attribute ID
     *
     * @return int
     */
    public function getDependentAttributeId()
    {
        return $this->_getData(self::DEPENDENT_ATTRIBUTE_ID);
    }

    /**
     * @param int $attributeId
     *
     * @return $this
     */
    public function setDependentAttributeId($attributeId)
    {
        $this->setData(self::DEPENDENT_ATTRIBUTE_ID, $attributeId);

        return $this;
    }

    /**
     * Returns Relation ID
     *
     * @return int
     */
    public function getRelationId()
    {
        return $this->_getData(self::RELATION_ID);
    }

    /**
     * @param int $relationId
     *
     * @return $this
     */
    public function setRelationId($relationId)
    {
        $this->setData(self::RELATION_ID, $relationId);

        return $this;
    }
}
