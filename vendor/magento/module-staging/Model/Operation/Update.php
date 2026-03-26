<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Operation;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\UpdateInterface;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Api\Data\UpdateInterface as UpdateInfo;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\Action\UpdateVersion;
use Magento\Staging\Model\Operation\Update\CampaignIntegrity;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Operation\Update\RescheduleUpdate;
use Magento\Staging\Model\Operation\Update\UpdateEntityVersion;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionInfo;
use Magento\Staging\Model\VersionInfoProvider;
use Magento\Staging\Model\VersionManager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Update implements UpdateInterface
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ReadEntityVersion
     */
    private $entityVersion;

    /**
     * @var UpdateVersion
     */
    private $updateVersion;

    /**
     * @var Update\CreateEntityVersion
     */
    private $createEntityVersion;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var UpdateEntityVersion
     */
    private $updateEntityVersion;

    /**
     * @var RescheduleUpdate
     */
    private $rescheduleUpdate;

    /**
     * @var CampaignIntegrity
     */
    private $campaignIntegrity;

    /**
     * @var VersionInfoProvider
     */
    private $versionInfoProvider;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param TypeResolver $typeResolver
     * @param ReadEntityVersion $entityVersion
     * @param MetadataPool $metadataPool
     * @param UpdateVersion $updateVersion
     * @param CreateEntityVersion $createEntityVersion
     * @param UpdateEntityVersion $updateEntityVersion
     * @param ResourceConnection $resourceConnection
     * @param EventManager $eventManager
     * @param UpdateRepositoryInterface $updateRepository
     * @param RescheduleUpdate $rescheduleUpdate
     * @param CampaignIntegrity $campaignIntegrity
     * @param VersionInfoProvider $versionInfoProvider
     * @param VersionManager $versionManager
     * @internal param PermanentUpdateProcessorPool $permanentUpdateProcessorPool
     * @internal param TemporaryUpdateProcessorPool $temporaryUpdateProcessorPool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TypeResolver $typeResolver,
        ReadEntityVersion $entityVersion,
        MetadataPool $metadataPool,
        UpdateVersion $updateVersion,
        CreateEntityVersion $createEntityVersion,
        UpdateEntityVersion $updateEntityVersion,
        ResourceConnection $resourceConnection,
        EventManager $eventManager,
        UpdateRepositoryInterface $updateRepository,
        RescheduleUpdate $rescheduleUpdate,
        CampaignIntegrity $campaignIntegrity,
        VersionInfoProvider $versionInfoProvider,
        VersionManager $versionManager
    ) {
        $this->typeResolver = $typeResolver;
        $this->entityVersion = $entityVersion;
        $this->metadataPool = $metadataPool;
        $this->updateVersion = $updateVersion;
        $this->createEntityVersion = $createEntityVersion;
        $this->resourceConnection = $resourceConnection;
        $this->eventManager = $eventManager;
        $this->updateRepository = $updateRepository;
        $this->updateEntityVersion = $updateEntityVersion;
        $this->rescheduleUpdate = $rescheduleUpdate;
        $this->campaignIntegrity = $campaignIntegrity;
        $this->versionInfoProvider = $versionInfoProvider;
        $this->versionManager = $versionManager;
    }

    /**
     * Resolve version id
     *
     * @param array $data
     * @return string|null
     */
    private function resolveVersion($data)
    {
        return isset($data['created_in']) ? $data['created_in'] : null;
    }

    /**
     * Process entity update
     *
     * @param EntityMetadataInterface $metadata
     * @param string $entityType
     * @param object $entity
     * @param array $arguments
     * @return void
     * @throws \Exception
     */
    private function processUpdate(
        EntityMetadataInterface $metadata,
        $entityType,
        $entity,
        $arguments
    ) {
        $hydrator = $this->metadataPool->getHydrator($entityType);
        $entityData = $hydrator->extract($entity);
        if (!isset($arguments['created_in'])) {
            $createdIn = array_key_exists('created_in', $entityData) ? $entityData['created_in'] : 1;
            $arguments['created_in'] = $createdIn;
        }
        $update = $this->updateRepository->get($arguments['created_in']);

        $needReschedule = false;
        if (isset($arguments['origin_in'])) {
            $needReschedule = $this->needsReschedule($update, $entityData, $arguments);

            if ($needReschedule) {
                $newVersionInfo = $this->rescheduleUpdate->reschedule(
                    $arguments['origin_in'],
                    $arguments['created_in'],
                    $entity
                );
                $this->processNewVersion($entity, $newVersionInfo, $hydrator);
            }
        }

        $identifier = $entityData[$metadata->getIdentifierField()];
        $version = $this->versionInfoProvider->getVersionInfo(
            $entity,
            $this->resolveVersion(array_merge($entityData, $arguments))
        );
        if ($version->getRowId() != null) {
            if ($update->getRollbackId()) {
                $arguments['updated_in'] = $update->getRollbackId();
            } else {
                $arguments['updated_in'] = $version->getUpdatedIn();
            }
            $arguments[$metadata->getLinkField()] = $version->getRowId();
            $rollbackEntity = clone $entity;
            $this->updateEntityVersion->execute($entity, $arguments);
        } else {
            $this->updateVersion->execute($entityType, $identifier);
            $arguments['updated_in'] = ($update->getRollbackId()) ?:
                $this->entityVersion->getNextVersionId($entityType, $update->getId(), $identifier);
            $this->createEntityVersion->execute($entity, $arguments);
        }
        $rollbackEntity = $rollbackEntity ?? $entity;
        $this->createRollback($update, $version, $rollbackEntity, $needReschedule);
        $this->campaignIntegrity->synchronizeAffectedCampaigns($update, $rollbackEntity, $version);
    }

    /**
     * @inheritdoc
     */
    public function execute($entity, $arguments = [])
    {
        $entityType = $this->typeResolver->resolve($entity);
        $metadata = $this->metadataPool->getMetadata($entityType);

        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $connection->beginTransaction();
        try {
            $this->eventManager->dispatch('entity_save_before', ['entity_type' => $entityType, 'entity' => $entity]);
            $this->eventManager->dispatchEntityEvent($entityType, 'save_before', ['entity' => $entity]);
            $this->processUpdate($metadata, $entityType, $entity, $arguments);
            $this->eventManager->dispatchEntityEvent($entityType, 'save_after', ['entity' => $entity]);
            $this->eventManager->dispatch(
                'entity_manager_save_after',
                [
                    'entity_type' => $entityType,
                    'entity' => $entity
                ]
            );
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
        return $entity;
    }

    /**
     * Create rollback point for the version if needed.
     *
     * @param UpdateInfo $update
     * @param VersionInfo $version
     * @param object $entity
     * @param bool $rescheduledUpdate
     * @return void
     */
    private function createRollback(UpdateInfo $update, VersionInfo $version, $entity, bool $rescheduledUpdate)
    {
        $newTemporary = $version->getRowId() === null;
        $permanentToTemporary = (
            !$update->getRollbackId()
            || (int)$version->getUpdatedIn() !== (int)$update->getRollbackId()
        );
        //Creating new rollback if the staged updated is a new temporary update,
        //or it was rescheduled:
        //Start date was moved
        //OR end date was added (new rollback for the version was created
        //so old "updated in" is not equal to the rollback ID).
        if ($newTemporary || $rescheduledUpdate || $permanentToTemporary) {
            $this->campaignIntegrity->createRollbackPoint($update, $entity);
        }
    }

    /**
     * Process new version entity
     *
     * @param object $entity
     * @param mixed $newVersionInfo
     * @param object $hydrator
     * @return void
     */
    private function processNewVersion(object $entity, mixed $newVersionInfo, object $hydrator) : void
    {
        if ($newVersionInfo !== null) {
            $hydrator->hydrate(
                $entity,
                [
                    'created_in' => $newVersionInfo->getCreatedIn(),
                    'updated_in' => $newVersionInfo->getUpdatedIn()
                ]
            );
            $this->versionManager->setCurrentVersionId($newVersionInfo->getCreatedIn());
        }
    }

    /**
     * Check if Update's start and/or end dates changed, and Update needs reschedule.
     *
     * @param UpdateInfo $update
     * @param array $entityData
     * @param array $arguments
     * @return bool
     */
    private function needsReschedule(UpdateInfo $update, array $entityData, array $arguments): bool
    {
         /*
         We check if the end date of the Update changed by comparing rollback id and 'updated in' field of the Entity.
         But when previous Update had the end date while the new one doesn't, then the new Update's rollback id is null,
         and we need to check if the old Update had the end date by getting the record by 'updated_in' field,
         and check if it is rollback. The absence of record returns true to preserve old existing behaviour
         */
        try {
            $updateFromEntityData = $this->updateRepository->get($entityData['updated_in']);
            $processEndDate = $updateFromEntityData->getIsRollback();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            $processEndDate = true;
        }

        return $arguments['created_in'] != $arguments['origin_in']
            || ($processEndDate && $update->getRollbackId() != $entityData['updated_in']);
    }
}
