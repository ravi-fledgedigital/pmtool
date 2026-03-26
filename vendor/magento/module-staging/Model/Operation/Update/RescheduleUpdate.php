<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Operation\Update;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionInfo;
use Magento\Staging\Model\VersionInfoFactory;
use Magento\Staging\Model\VersionManager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RescheduleUpdate
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var HydratorPool
     */
    private $hydratorPool;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var VersionInfoFactory
     */
    private VersionInfoFactory $versionInfoFactory;

    /**
     * RescheduleUpdate constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @param HydratorPool $hydratorPool
     * @param TypeResolver $typeResolver
     * @param UpdateRepositoryInterface $updateRepository
     * @param VersionInfoFactory $versionInfoFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        HydratorPool $hydratorPool,
        TypeResolver $typeResolver,
        UpdateRepositoryInterface $updateRepository,
        VersionInfoFactory $versionInfoFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->hydratorPool = $hydratorPool;
        $this->typeResolver = $typeResolver;
        $this->updateRepository = $updateRepository;
        $this->versionInfoFactory = $versionInfoFactory;
    }

    /**
     * Returns previous created_in
     *
     * @param EntityMetadataInterface $metadata
     * @param string $version
     * @param string $identifier
     * @param null|string $excludedOriginVersion
     * @return int
     */
    private function getPrevious(
        EntityMetadataInterface $metadata,
        $version,
        $identifier,
        $excludedOriginVersion = null
    ) {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $select = $connection->select()
            ->from(
                ['t' => $metadata->getEntityTable()],
                ['created_in']
            )
            ->where('t.created_in < ?', $version)
            ->where('t.' . $metadata->getIdentifierField() . ' = ?', $identifier)
            ->order('t.created_in DESC')
            ->limit(1)
            ->setPart('disable_staging_preview', true);
        if ($excludedOriginVersion !== null) {
            $select->where('t.created_in != ?', $excludedOriginVersion);
        }
        $previous = $connection->fetchOne($select);

        return $previous ?: VersionManager::MIN_VERSION;
    }

    /**
     * Returns previous created_in for rollback
     *
     * @param EntityMetadataInterface $metadata
     * @param string $version
     * @param string $identifier
     * @return int
     */
    private function getPreviousForRollback(EntityMetadataInterface $metadata, $version, $identifier)
    {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $select = $connection->select()
            ->from(
                ['t' => $metadata->getEntityTable()],
                ['created_in']
            )
            ->where('t.created_in < ?', $version)
            ->where('t.' . $metadata->getIdentifierField() . ' = ?', $identifier)
            ->order('t.created_in DESC')
            ->limit(1)
            ->setPart('disable_staging_preview', true);
        return $connection->fetchOne($select);
    }

    /**
     * Returns next created_in for rollback
     *
     * @param EntityMetadataInterface $metadata
     * @param string $version
     * @param string $identifier
     * @return int
     */
    private function getNextForRollback(EntityMetadataInterface $metadata, $version, $identifier)
    {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $select = $connection->select()
            ->from(
                ['t' => $metadata->getEntityTable()],
                ['created_in']
            )
            ->where('t.created_in > ?', $version)
            ->where('t.' . $metadata->getIdentifierField() . ' = ?', $identifier)
            ->order('t.created_in ASC')
            ->limit(1)
            ->setPart('disable_staging_preview', true);
        return $connection->fetchOne($select);
    }

    /**
     * Returns next created_in
     *
     * @param EntityMetadataInterface $metadata
     * @param string $version
     * @param string $identifier
     * @param null|string $excludedOriginVersion
     * @return int
     */
    private function getNext(EntityMetadataInterface $metadata, $version, $identifier, $excludedOriginVersion = null)
    {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $select = $connection->select()
            ->from(
                ['t' => $metadata->getEntityTable()],
                ['created_in']
            )
            ->where('t.created_in > ?', $version)
            ->where('t.' . $metadata->getIdentifierField() . ' = ?', $identifier)
            ->order('t.created_in ASC')
            ->limit(1)
            ->setPart('disable_staging_preview', true);
        if ($excludedOriginVersion !== null) {
            $select->where('t.created_in != ?', $excludedOriginVersion);
        }
        $next = $connection->fetchOne($select);

        return $next ?: VersionManager::MAX_VERSION;
    }

    /**
     * Purge old interval
     *
     * @param EntityMetadataInterface $metadata
     * @param string $originVersion
     * @param string $targetVersion
     * @param string $identifier
     * @return void
     */
    private function purgeOldInterval(EntityMetadataInterface $metadata, $originVersion, $targetVersion, $identifier)
    {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $previous = $this->getPrevious($metadata, $originVersion, $identifier, $originVersion);
        $next = $this->getNext($metadata, $originVersion, $identifier, $originVersion);
        $updatedIn = ($targetVersion < $next && $targetVersion > $originVersion) ? $targetVersion : $next;
        $connection->update(
            $metadata->getEntityTable(),
            ['updated_in' => $updatedIn],
            [
                $metadata->getIdentifierField() . ' = ?' => $identifier,
                'created_in = ?' => $previous
            ]
        );
    }

    /**
     * Prepares new interval
     *
     * @param EntityMetadataInterface $metadata
     * @param string $originVersion
     * @param string $targetVersion
     * @param string $identifier
     * @return void
     */
    private function prepareNewInterval(EntityMetadataInterface $metadata, $originVersion, $targetVersion, $identifier)
    {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $previous = $this->getPrevious($metadata, $targetVersion, $identifier, $originVersion);
        $connection->update(
            $metadata->getEntityTable(),
            ['updated_in' => $targetVersion],
            [
                $metadata->getIdentifierField() . ' = ?' => $identifier,
                'created_in = ?' => $previous
            ]
        );
    }

    /**
     * Update entity
     *
     * @param EntityMetadataInterface $metadata
     * @param UpdateInterface $origin
     * @param UpdateInterface $target
     * @param string $identifier
     * @return array|null
     */
    private function updateEntry(
        EntityMetadataInterface $metadata,
        UpdateInterface $origin,
        UpdateInterface $target,
        $identifier
    ) {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        if ($target->getRollbackId()) {
            $updateIn = $target->getRollbackId();
        } else {
            $updateIn = $this->getNext($metadata, $target->getId(), $identifier, $origin->getId());
        }
        $updatedData = [
            'updated_in' => $updateIn,
            'created_in' => $target->getId()
        ];
        $isUpdated = $connection->update(
            $metadata->getEntityTable(),
            $updatedData,
            [
                $metadata->getIdentifierField() . ' = ?' => $identifier,
                'created_in = ?' => $origin->getId()
            ]
        );
        return $isUpdated ? $updatedData : null;
    }

    /**
     * Removes rollback entry
     *
     * @param string $entityType
     * @param object $entity
     * @param UpdateInterface $origin
     * @return bool
     * @throws \Zend_Db_Select_Exception
     */
    private function purgeRollbackEntry($entityType, $entity, UpdateInterface $origin)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $hydrator = $this->hydratorPool->getHydrator($entityType);
        $entityData = $hydrator->extract($entity);
        $identifier = $entityData[$metadata->getIdentifierField()];
        $rollbackId = $origin->getRollbackId() ?: $entityData['updated_in'];
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        try {
            $rollbackInfo = $this->updateRepository->get($rollbackId);
        } catch (NoSuchEntityException $exception) {
            $rollbackInfo = false;
        }
        $select = $connection->select()
            ->from(
                ['t' => $metadata->getEntityTable()],
                ['updated_in']
            )
            ->where('t.created_in = ?', $rollbackId)
            ->where('t.' . $metadata->getIdentifierField() . ' = ?', $identifier)
            ->order('t.created_in DESC')
            ->limit(1)
            ->setPart('disable_staging_preview', true);
        $futureUpdate = $connection->fetchOne($select);
        if (empty($futureUpdate) || $rollbackInfo === false || $rollbackInfo->getIsRollback()) {
            $connection->update(
                $metadata->getEntityTable(),
                [
                    'updated_in' => $this->getNextForRollback($metadata, $rollbackId, $identifier),
                ],
                [
                    $metadata->getIdentifierField() . ' = ?' => $identifier,
                    'created_in = ?' => $this->getPreviousForRollback($metadata, $rollbackId, $identifier)
                ]
            );
            $connection->delete(
                $metadata->getEntityTable(),
                [
                    $metadata->getIdentifierField() . ' = ?' => $identifier,
                    'created_in = ?' => $rollbackId
                ]
            );
        }

        return true;
    }

    /**
     * Moves entity version
     *
     * @param string $entityType
     * @param object $entity
     * @param UpdateInterface $origin
     * @param UpdateInterface $target
     * @return array
     */
    private function moveEntityVersion($entityType, $entity, UpdateInterface $origin, UpdateInterface $target)
    {
        $originVersion = $origin->getId();
        $targetVersion = $target->getId();
        $metadata = $this->metadataPool->getMetadata($entityType);
        $hydrator = $this->hydratorPool->getHydrator($entityType);
        $entityData = $hydrator->extract($entity);
        $identifier = $entityData[$metadata->getIdentifierField()];
        $this->purgeOldInterval($metadata, $originVersion, $targetVersion, $identifier);
        $this->prepareNewInterval($metadata, $originVersion, $targetVersion, $identifier);
        return $this->updateEntry($metadata, $origin, $target, $identifier);
    }

    /**
     * Reschedules update for entity
     *
     * @param string $originVersion
     * @param string $targetVersion
     * @param object $entity
     * @return VersionInfo|null
     * @throws Exception
     */
    public function reschedule($originVersion, $targetVersion, object $entity): null|VersionInfo
    {
        return $this->rescheduleAndGetVersionInfo($originVersion, $targetVersion, $entity);
    }

    /**
     * Reschedules update and get version information for entity
     *
     * @param string $originVersion
     * @param string $targetVersion
     * @param object $entity
     * @return null|VersionInfo
     * @throws Exception
     */
    public function rescheduleAndGetVersionInfo($originVersion, $targetVersion, object $entity)
    {
        $origin = $this->updateRepository->get($originVersion);
        $target = $this->updateRepository->get($targetVersion);
        $entityType = $this->typeResolver->resolve($entity);
        $metadata = $this->metadataPool->getMetadata($entityType);
        $hydrated = $this->hydratorPool->getHydrator($entityType);
        $entityData = $hydrated->extract($entity);
        $identifierField = $metadata->getIdentifierField();
        $linkField = $metadata->getLinkField();
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $connection->beginTransaction();
        try {
            if ($origin->getRollbackId() || !$target->getRollbackId()) {
                $this->purgeRollbackEntry($entityType, $entity, $origin);
            }
            $data =  $this->moveEntityVersion($entityType, $entity, $origin, $target);
            $connection->commit();
            return $this->versionInfoFactory->create(
                [
                    'rowId' => $entityData[$linkField] ?? null,
                    'identifier' => $entityData[$identifierField] ?? null,
                    'createdIn' => $data['created_in'] ?? null,
                    'updatedIn' => $data['updated_in'] ?? null
                ]
            );
        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
