<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Api\Data;

interface CheckoutEntityInterface
{
    /**#@+
     * Values for parent_entity_type
     */
    public const ENTITY_TYPE_ORDER = 1;
    public const ENTITY_TYPE_QUOTE = 2;
    /**#@-*/

    /**#@+
     * Constants defined for keys of data array
     */
    public const ENTITY_ID = 'entity_id';
    public const PARENT_ID = 'parent_id';
    public const PARENT_ENTITY_TYPE = 'parent_entity_type';
    /**#@-*/

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entityId
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutEntityInterface
     */
    public function setEntityId($entityId);

    /**
     * @return int
     */
    public function getParentId();

    /**
     * @param int $parentId
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutEntityInterface
     */
    public function setParentId($parentId);

    /**
     * @return int
     */
    public function getParentEntityType();

    /**
     * @param int $parentEntityType
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutEntityInterface
     */
    public function setParentEntityType($parentEntityType);
}
