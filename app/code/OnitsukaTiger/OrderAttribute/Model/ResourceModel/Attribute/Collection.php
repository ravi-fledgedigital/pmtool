<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use OnitsukaTiger\OrderAttribute\Model\Config\Source\CheckoutStep;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Attribute as AttributeResource;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity;

/**
 * @method \OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute[] getItems()
 */
class Collection extends \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
{
    /**
     * Resource model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute::class,
            AttributeResource::class
        );
    }

    /**
     * Initialize select object
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $entityType = $this->eavConfig->getEntityType(Entity::ENTITY_TYPE_CODE);
        $this->setEntityTypeFilter($entityType);
        return $this;
    }

    /**
     * Specify "is_filterable" filter
     *
     * @return $this
     */
    public function addGridFilter()
    {
        return $this->addFieldToFilter('additional_table.is_filterable', ['gt' => 0]);
    }

    /**
     * Specify "checkout_step" filter
     *
     * @return $this
     */
    public function addFilterUnassignedOnCheckout()
    {
        return $this->addFieldToFilter(
            'additional_table.' . CheckoutAttributeInterface::CHECKOUT_STEP,
            ['neq' => CheckoutStep::NONE]
        );
    }

    /**
     * Specify "is_filterable" filter
     *
     * @return $this
     */
    public function addIsFilterableFilter()
    {
        return $this->addFieldToFilter('additional_table.' . CheckoutAttributeInterface::SHOW_ON_GRIDS, ['gt' => 0]);
    }

    /**
     * Add store filter
     *
     * @param int|array $storeId
     * @return $this
     */
    public function addStoreFilter($storeId)
    {
        if (!is_array($storeId)) {
            $storeId = [$storeId];
        }

        $this->join(
            AttributeResource::STORE_TABLE_NAME,
            AttributeResource::STORE_TABLE_NAME . '.' . CheckoutAttributeInterface::ATTRIBUTE_ID
                . '= main_table.' . CheckoutAttributeInterface::ATTRIBUTE_ID,
            null
        );

        $this->addFieldToFilter(AttributeResource::STORE_TABLE_NAME . '.store_id', ['in' => $storeId]);

        return $this;
    }

    /**
     * Add customer group filter
     *
     * @param int $groupId
     * @return $this
     */
    public function addCustomerGroupFilter($groupId)
    {
        $this->join(
            AttributeResource::CUSTOMER_GROUP_TABLE_NAME,
            AttributeResource::CUSTOMER_GROUP_TABLE_NAME . '.' . CheckoutAttributeInterface::ATTRIBUTE_ID
                . '= main_table.' . CheckoutAttributeInterface::ATTRIBUTE_ID,
            null
        );
        $this->addFieldToFilter(
            AttributeResource::CUSTOMER_GROUP_TABLE_NAME. '.customer_group_id',
            (int) $groupId
        );
        return $this;
    }

    /**
     * Add shipping method filter
     *
     * @param string $shippingMethod
     * @return $this
     */
    public function addShippingMethodsFilter($shippingMethod)
    {
        $this->joinLeft(
            AttributeResource::SHIPPING_METHODS_TABLE_NAME,
            'main_table.' . CheckoutAttributeInterface::ATTRIBUTE_ID . '='
                . AttributeResource::SHIPPING_METHODS_TABLE_NAME . '.' . CheckoutAttributeInterface::ATTRIBUTE_ID,
            null
        )->addFieldToFilter(
            AttributeResource::SHIPPING_METHODS_TABLE_NAME . '.shipping_method',
            [
                ['null' => true],
                ['eq' => $shippingMethod]
            ]
        );

        return $this;
    }

    /**
     * Set order by attribute sort order
     *
     * @param string $direction
     * @return $this
     */
    public function setSortOrder($direction = self::SORT_ORDER_ASC)
    {
        return $this->setOrder(
            'additional_table.' . \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface::SORTING_ORDER,
            $direction
        );
    }
}
