<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Setup\Patch\Data;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutEntityInterface;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity as EntityResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ChangeDuplicateEntries implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    private $entityTable;

    /**
     * @var string[]
     */
    private $tableKeys = [
        'datetime',
        'decimal',
        'int',
        'text',
        'varchar'
    ];

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function apply(): self
    {
        $this->entityTable = $this->resourceConnection->getTableName(EntityResource::TABLE_NAME);
        $this->deleteDuplicatesForQuote();
        $newEntityIds = $this->generateNewEntityIds();
        $this->changeIdsInTables($newEntityIds);

        return $this;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Clear Duplicate Entities for Quotes
     */
    private function deleteDuplicatesForQuote(): void
    {
        $duplicateIds = $this->getDuplicateIdsByType(CheckoutEntityInterface::ENTITY_TYPE_QUOTE);
        $this->resourceConnection->getConnection()->delete(
            $this->entityTable,
            [
                CheckoutEntityInterface::ENTITY_ID . ' IN(?)' => $duplicateIds,
                CheckoutEntityInterface::PARENT_ENTITY_TYPE . ' =?' => CheckoutEntityInterface::ENTITY_TYPE_QUOTE
            ]
        );
    }

    /**
     * @return array array('old_id' => array('new_id', ...), ...)
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    private function generateNewEntityIds(): array
    {
        $duplicateIds = $this->getDuplicateIdsByType(CheckoutEntityInterface::ENTITY_TYPE_ORDER);
        $increment = 1;
        $newEntityIds = [];
        $lastEntityId = $this->getLastEntityId();
        foreach ($duplicateIds as $count => $duplicateId) {
            for ($i = 0; $i < $count; $i++) {
                $newEntityIds[$duplicateId][] = $lastEntityId + $increment;
                $increment++;
            }
        }

        return $newEntityIds;
    }

    /**
     * @param array $newEntityIds
     */
    private function changeIdsInTables(array $newEntityIds): void
    {
        foreach ($newEntityIds as $oldId => $newIds) {
            $this->changeIdsInEntityTable((int)$oldId, $newIds);
            $this->changeIdsInValueTables((int)$oldId, $newIds);
        }
    }

    /**
     * @param int $oldId
     * @param array $newIds
     */
    private function changeIdsInEntityTable(int $oldId, array $newIds): void
    {
        $duplicates = $this->getDuplicateEntitiesById($oldId);
        foreach ($duplicates as $entity) {
            $firstKey = array_key_first($newIds);
            $this->updateIdInEntityTable($entity, $newIds[$firstKey]);
            unset($newIds[$firstKey]);
        }
    }

    /**
     * @param int $entityId
     * @return array
     */
    private function getDuplicateEntitiesById(int $entityId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->entityTable)
            ->where(CheckoutEntityInterface::ENTITY_ID . ' =?', $entityId)
            ->where(CheckoutEntityInterface::PARENT_ENTITY_TYPE . ' =?', CheckoutEntityInterface::ENTITY_TYPE_ORDER)
            ->order('parent_id' . ' DESC');

        return $connection->fetchAll($select);
    }

    /**
     * @param array $entity
     * @param int $newId
     */
    private function updateIdInEntityTable(array $entity, int $newId): void
    {
        $this->resourceConnection->getConnection()->update(
            $this->entityTable,
            [CheckoutEntityInterface::ENTITY_ID => $newId],
            [
                CheckoutEntityInterface::ENTITY_ID . ' = ?' => $entity[CheckoutEntityInterface::ENTITY_ID],
                CheckoutEntityInterface::PARENT_ID . ' = ?' => $entity[CheckoutEntityInterface::PARENT_ID],
                CheckoutEntityInterface::PARENT_ENTITY_TYPE . ' = ?' => CheckoutEntityInterface::ENTITY_TYPE_ORDER
            ]
        );
    }

    /**
     * @param int $oldId
     * @param array $newIds
     */
    private function changeIdsInValueTables(int $oldId, array $newIds): void
    {
        foreach ($this->tableKeys as $tableKey) {
            $this->changeIdsInValueTable($tableKey, $oldId, $newIds);
        }
    }

    /**
     * @param string $tableKey
     * @param int $oldId
     * @param array $newIds
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function changeIdsInValueTable(string $tableKey, int $oldId, array $newIds): void
    {
        $entity = $this->getExistValueEntity($tableKey, $oldId);
        if (!$entity) {
            return;
        }

        $existEntityFlag = true;
        foreach ($newIds as $newId) {
            if ($existEntityFlag) {
                $this->updateIdInValueEntityTable($tableKey, $oldId, (int)$newId);
                $existEntityFlag = false;
            } else {
                unset($entity['value_id']);
                $entity['entity_id'] = $newId;
                $this->insertNewValueEntity($tableKey, $entity);

            }
        }
    }

    /**
     * @param string $tableKey
     * @param int $entityId
     * @return array|bool
     */
    private function getExistValueEntity(string $tableKey, int $entityId)
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->resourceConnection->getTableName(EntityResource::TABLE_NAME . '_' . $tableKey))
            ->where(CheckoutEntityInterface::ENTITY_ID . ' =?', $entityId);

        return $connection->fetchRow($select);
    }

    /**
     * @param string $tableKey
     * @param int $oldId
     * @param int $newId
     */
    private function updateIdInValueEntityTable(string $tableKey, int $oldId, int $newId): void
    {
        $this->resourceConnection->getConnection()->update(
            $this->resourceConnection->getTableName(EntityResource::TABLE_NAME . '_' . $tableKey),
            ['entity_id' => $newId],
            ['entity_id = ?' => $oldId]
        );
    }

    /**
     * @param string $tableKey
     * @param array $valueEntity
     */
    private function insertNewValueEntity(string $tableKey, array $valueEntity): void
    {
        $this->resourceConnection->getConnection()->insertMultiple(
            $this->resourceConnection->getTableName(EntityResource::TABLE_NAME . '_' . $tableKey),
            $valueEntity
        );
    }

    /**
     * @return int
     */
    private function getLastEntityId(): int
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->entityTable, [CheckoutEntityInterface::ENTITY_ID])
            ->order(CheckoutEntityInterface::ENTITY_ID . ' DESC')->limitPage(1, 1);

        return (int) $connection->fetchOne($select);
    }

    /**
     * @param int $type
     * @return array array('count_of_duplicates' => 'entity_id', ...)
     */
    private function getDuplicateIdsByType(int $type): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->entityTable, ['COUNT(*)', CheckoutEntityInterface::ENTITY_ID])
            ->where(CheckoutEntityInterface::PARENT_ENTITY_TYPE . ' =?', $type)
            ->group([CheckoutEntityInterface::ENTITY_ID])
            ->having('COUNT(*) > 1');

        return $connection->fetchPairs($select);
    }
}
