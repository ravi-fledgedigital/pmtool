<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity;

use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutEntityInterface;
use OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * EAV Resource model
 */
class Entity extends \Magento\Eav\Model\Entity\AbstractEntity
{
    public const ENTITY_TYPE_CODE = 'onitsukatiger_checkout';

    public const GRID_INDEXER_ID = 'onitsukatiger_order_attribute_grid';

    public const INITIAL_AUTOINCREMENT_VALUE = 1;

    public const TABLE_NAME = 'onitsukatiger_order_attribute_entity';

    /**
     * @var string
     */
    protected $linkIdField = 'entity_id';

    /**
     * @var string
     */
    protected $_entityIdField = 'entity_id';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setType(self::ENTITY_TYPE_CODE);
    }

    /**
     * Get codes of all entity type attributes
     *
     * @param  \Magento\Framework\DataObject $object
     * @return array
     */
    public function getAttributeCodes($object = null)
    {
        return $this->_getConfig()->getEntityAttributeCodes($this->getType(), $object);
    }

    /**
     * Check whether the attribute is Applicable to the object
     *
     * @param   \Magento\Framework\DataObject $object
     * @param   AbstractAttribute             $attribute
     *
     * @return  bool
     */
    protected function _isApplicableAttribute($object, $attribute)
    {
        if ($attribute instanceof \OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute) {
            return $object instanceof \Magento\Sales\Model\EntityInterface;
        }

        return parent::_isApplicableAttribute($object, $attribute);
    }

    /**
     * Load the Entity by Order relation
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param int                                       $orderId
     * @param array                                  $attributes
     *
     * @return $this
     */
    public function loadByOrderId(\Magento\Framework\Model\AbstractModel $object, $orderId, $attributes = [])
    {
        $this->loadByParentId($object, $orderId, EntityDataInterface::ENTITY_TYPE_ORDER, $attributes);

        return $this;
    }

    /**
     * Load the Entity by Quote relation
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param int                                    $quoteId
     * @param array                                  $attributes
     *
     * @return $this
     */
    public function loadByQuoteId(\Magento\Framework\Model\AbstractModel $object, $quoteId, $attributes = [])
    {
        $this->loadByParentId($object, $quoteId, EntityDataInterface::ENTITY_TYPE_QUOTE, $attributes);

        return $this;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel|EntityDataInterface $object
     * @param int                                                        $parentEntityId
     * @param int                                                        $parentEntityType
     * @param array                                                      $attributes
     *
     * @return $this
     */
    public function loadByParentId(
        $object,
        $parentEntityId,
        $parentEntityType = EntityDataInterface::ENTITY_TYPE_ORDER,
        $attributes = []
    ) {
        \Magento\Framework\Profiler::start('CHECKOUT_EAV:load_entity');
        /**
         * Load object base row data
         */
        $select = $this->getLoadRowSelectByParentId($parentEntityId, $parentEntityType);
        $row = $this->getConnection()->fetchRow($select);

        if (is_array($row)) {
            $object->addData($row);
            $this->loadAttributesForObject($attributes, $object);

            $this->_loadModelAttributes($object);
            $this->_afterLoad($object);
            $object->afterLoad();
            $object->setOrigData();
            $object->setHasDataChanges(false);
        } else {
            $object->isObjectNew(true);
        }

        \Magento\Framework\Profiler::stop('CHECKOUT_EAV:load_entity');
        return $this;
    }

    /**
     * @param int $parentEntity
     * @param int $parentEntityType
     *
     * @return \Magento\Framework\DB\Select
     */
    protected function getLoadRowSelectByParentId($parentEntity, $parentEntityType)
    {
        $select = $this->getConnection()->select()->from(
            $this->getEntityTable()
        )->where(
            CheckoutEntityInterface::PARENT_ID . ' =?',
            $parentEntity,
            \Zend_Db::INT_TYPE
        )->where(
            CheckoutEntityInterface::PARENT_ENTITY_TYPE . ' =?',
            $parentEntityType,
            \Zend_Db::INT_TYPE
        );

        return $select;
    }

    /**
     * Load attributes for object
     *  if the object will not pass all attributes for this entity type will be loaded
     *
     * @param array $attributes
     * @param AbstractEntity|null $object
     * @return void
     */
    protected function loadAttributesForObject($attributes, $object = null)
    {
        if (empty($attributes)) {
            $this->loadAllAttributes($object);
        } else {
            if (!is_array($attributes)) {
                $attributes = [$attributes];
            }
            foreach ($attributes as $attrCode) {
                $this->getAttribute($attrCode);
            }
        }
    }

    /**
     * Generate new entity_id to object
     * entity_id can be not unique in checkout attributes
     *
     * This method resolves the race condition problem when customers try to save two or more
     * order attributes at the same time and records receive same entity IDs.
     * Entity ID receives the next auto increment value and automatically increases auto increment value
     * simultaneously to make sure that the next order attribute to be saved won't receive the same entity ID.
     * The logic of picking the next entity ID has been moved to the save handler
     * (@see \OnitsukaTiger\OrderAttribute\Model\Entity\Handler\Save) because DDL statements are not allowed in transactions.
     *
     * @return int
     */
    public function reserveEntityId()
    {
        $nextEntityId = $this->getNextAutoIncrement();
        $this->updateAutoIncrement($nextEntityId + 1);
        return $nextEntityId;
    }

    /**
     * @param int $autoIncrement
     */
    protected function updateAutoIncrement(int $autoIncrement): void
    {
        $this->getConnection()->query(
            sprintf(
                'ALTER TABLE %s AUTO_INCREMENT = %s',
                $this->getConnection()->quoteTableAs($this->getEntityTable()),
                $autoIncrement
            )
        );
    }

    /**
     * @return int
     */
    protected function getNextAutoIncrement(): int
    {
        $tableStatus = $this->getConnection()->showTableStatus($this->getEntityTable());
        if ($tableStatus && !empty($tableStatus['Auto_increment'])) {
            return $tableStatus['Auto_increment'];
        }

        return self::INITIAL_AUTOINCREMENT_VALUE;
    }

    protected function _collectSaveData($object)
    {
        return $this->addToDeleteHiddenByRelationAttributeValues(
            $object,
            parent::_collectSaveData($object)
        );
    }

    /**
     * @param AbstractModel $object
     * @param array $result
     */
    private function addToDeleteHiddenByRelationAttributeValues(AbstractModel $object, array $result): array
    {
        $forbiddenCodes = $object->getForbiddenAttributeCodes() ?? [];

        if (empty($forbiddenCodes)) {
            return $result;
        }

        $delete = [];
        /** @var AbstractAttribute $attribute */
        foreach ($forbiddenCodes as $code) {
            $attribute = $this->getAttribute($code);
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $affectedFields = $attribute->getBackend()->getAffectedFields($object);

            foreach ($affectedFields as $key => $fields) {
                foreach ($fields as $field) {
                    $delete[$key][] = $field;
                }
            }
        }
        $result['delete'] = array_merge($result['delete'], $delete);

        return $result;
    }

    /**
     * Save object collected data
     * Overridden because in Order Attributes entity_id is not unique
     *
     * @param   array $saveData array('newObject', 'entityRow', 'insert', 'update', 'delete')
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _processSaveData($saveData)
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
        extract($saveData, EXTR_SKIP);
        /**
         * Import variables into the current symbol table from save data array
         *
         * @see \Magento\Eav\Model\Entity\AbstractEntity::_collectSaveData()
         *
         * @var array $entityRow
         * @var \Magento\Framework\Model\AbstractModel|CheckoutEntityInterface $newObject
         * @var array $insert
         * @var array $update
         * @var array $delete
         */
        $connection = $this->getConnection();
        $insertEntity = true;
        $entityTable = $this->getEntityTable();
        $entityIdField = $this->getEntityIdField();
        $entityId = $newObject->getId();

        unset($entityRow[$entityIdField]);
        if (!empty($entityId) && is_numeric($entityId)) {
            // make entity Duplicate with same entity_id
            $bind = [
                CheckoutEntityInterface::PARENT_ENTITY_TYPE => $newObject->getParentEntityType(),
                CheckoutEntityInterface::PARENT_ID => $newObject->getParentId(),
                CheckoutEntityInterface::ENTITY_ID => $entityId
            ];
            $select = $connection->select()
                ->from($entityTable, $entityIdField)
                ->where(CheckoutEntityInterface::ENTITY_ID . " = :" . CheckoutEntityInterface::ENTITY_ID)
                ->where(CheckoutEntityInterface::PARENT_ID . " = :" . CheckoutEntityInterface::PARENT_ID)
                ->where(
                    CheckoutEntityInterface::PARENT_ENTITY_TYPE . " = :"
                    . CheckoutEntityInterface::PARENT_ENTITY_TYPE
                );
            $result = $connection->fetchOne($select, $bind);
            if ($result) {
                $insertEntity = false;
            }
        } else {
            $entityId = null;
        }

        /**
         * Process base row
         */
        $entityObject = new \Magento\Framework\DataObject($entityRow);
        $entityRow = $this->_prepareDataForTable($entityObject, $entityTable);
        if ($insertEntity) {
            if (!empty($entityId)) {
                $entityRow[$entityIdField] = $entityId;
                $connection->insertOnDuplicate($entityTable, $entityRow);
            } else {
                $connection->insert($entityTable, $entityRow);
                $entityId = $connection->lastInsertId($entityTable);
            }
            $newObject->setId($entityId);
        } else {
            $where = sprintf('%s=%d', $connection->quoteIdentifier($entityIdField), $entityId);
            $where .= sprintf(
                ' AND %s=%d',
                $connection->quoteIdentifier(CheckoutEntityInterface::PARENT_ID),
                $newObject->getParentId()
            );
            $where .= sprintf(
                ' AND %s=%d',
                $connection->quoteIdentifier(CheckoutEntityInterface::PARENT_ENTITY_TYPE),
                CheckoutEntityInterface::PARENT_ENTITY_TYPE
            );
            $connection->update($entityTable, $entityRow, $where);
        }

        /**
         * insert attribute values
         */
        if (!empty($insert)) {
            foreach ($insert as $attributeId => $value) {
                $attribute = $this->getAttribute($attributeId);
                $this->_insertAttribute($newObject, $attribute, $value);
            }
        }

        /**
         * update attribute values
         */
        if (!empty($update)) {
            foreach ($update as $attributeId => $v) {
                $attribute = $this->getAttribute($attributeId);
                $this->_updateAttribute($newObject, $attribute, $v['value_id'], $v['value']);
            }
        }

        /**
         * delete empty attribute values
         */
        if (!empty($delete)) {
            foreach ($delete as $table => $values) {
                $this->_deleteAttributes($newObject, $table, $values);
            }
        }

        $this->_processAttributeValues();

        $newObject->isObjectNew(false);

        return $this;
    }

    /**
     * Retrieve default entity attributes
     *
     * @return string[]
     */
    protected function _getDefaultAttributes()
    {
        return [CheckoutEntityInterface::PARENT_ID, CheckoutEntityInterface::PARENT_ENTITY_TYPE];
    }

    /**
     * Retrieve default entity static attributes
     *
     * @return string[]
     */
    public function getDefaultAttributes()
    {
        return array_unique(array_merge(
            $this->_getDefaultAttributes(),
            [$this->getEntityIdField()]
        ));
    }
}
