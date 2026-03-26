<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\Relation;

use OnitsukaTiger\OrderAttribute\Api\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\ValidatorException;

class RelationRepository implements \OnitsukaTiger\OrderAttribute\Api\RelationRepositoryInterface
{
    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation
     */
    protected $relationResource;

    /**
     * @var RelationFactory
     */
    protected $relationFactory;

    /**
     * @var array
     */
    protected $relations = [];

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails
     */
    private $detailResource;

    /**
     * RelationRepository constructor.
     * @param \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation $relationResource
     * @param \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails $detailResource
     * @param RelationFactory $relationFactory
     */
    public function __construct(
        \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation $relationResource,
        \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails $detailResource,
        \OnitsukaTiger\OrderAttribute\Model\Attribute\Relation\RelationFactory $relationFactory
    ) {
        $this->relationResource = $relationResource;
        $this->relationFactory = $relationFactory;
        $this->detailResource = $detailResource;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Data\RelationInterface $relation)
    {
        if ($relation->getRelationId()) {
            $relation = $this->get($relation->getRelationId())->addData($relation->getData());
        }
        try {
            $this->relationResource->save($relation);
            unset($this->relations[$relation->getId()]);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save relation %1', $relation->getRelationId()));
        }
        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function get($relationId)
    {
        if (!isset($this->relations[$relationId])) {
            $relation = $this->relationFactory->create();
            $this->relationResource->load($relation, $relationId);
            if (!$relation->getRelationId()) {
                throw new NoSuchEntityException(__('Relation with specified ID "%1" not found.', $relationId));
            }
            $this->relations[$relationId] = $relation;
        }
        return $this->relations[$relationId];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Data\RelationInterface $relation)
    {
        try {
            $this->detailResource->deleteAllDetailForRelation($relation->getRelationId());
            $this->relationResource->delete($relation);
            unset($this->relations[$relation->getId()]);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Unable to remove relation %1', $relation->getRelationId()));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($relationId)
    {
        $model = $this->get($relationId);
        $this->delete($model);
        return true;
    }
}
