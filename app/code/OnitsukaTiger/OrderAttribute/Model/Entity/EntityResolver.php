<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Entity;

use OnitsukaTiger\OrderAttribute\Model\Entity\EntityData;

class EntityResolver
{
    /**
     * @var EntityDataFactory
     */
    private $entityDataFactory;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity
     */
    private $attributeEntityResource;

    /**
     * object storage
     * @var array
     */
    protected $modelStorageQuote = [];

    /**
     * object storage
     * @var array
     */
    protected $modelStorageOrder = [];

    public function __construct(
        \OnitsukaTiger\OrderAttribute\Model\Entity\EntityDataFactory $entityDataFactory,
        \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity $attributeEntityResource
    ) {
        $this->entityDataFactory = $entityDataFactory;
        $this->attributeEntityResource = $attributeEntityResource;
    }

    /**
     * Get reletad entity, load attributes, load attribute values
     * return Entity with Attribute Data
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return EntityData
     */
    public function getEntityByOrder(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        return $this->getEntityByOrderId($order->getEntityId(), $order->getQuoteId());
    }

    /**
     * Get reletad entity, load attributes, load attribute values
     * return Entity with Attribute Data
     *
     * @param int $orderId
     * @param int|null $quoteId
     *
     * @return EntityData
     */
    public function getEntityByOrderId($orderId, $quoteId = null)
    {
        if (!isset($this->modelStorageOrder[$orderId])) {
            $entity = $this->createEntityModel();
            if ($orderId) {
                $this->attributeEntityResource->loadByOrderId($entity, $orderId);
            }
            if ($entity->isObjectNew()) {
                if ($quoteId) {
                    $entity = clone $this->getEntityByQuoteId($quoteId);
                }
                $entity->setParentId($orderId);
                $entity->setParentEntityType(EntityData::ENTITY_TYPE_ORDER);
            }
            $this->modelStorageOrder[$orderId] = $entity;
        }

        return $this->modelStorageOrder[$orderId];
    }

    /**
     * Get related entity, load attributes, load attribute values
     * return Entity with Attribute Data
     *
     * @param int $quoteId
     *
     * @return EntityData
     */
    public function getEntityByQuoteId($quoteId)
    {
        if (!isset($this->modelStorageQuote[$quoteId])) {
            $entity = $this->createEntityModel();
            $this->attributeEntityResource->loadByQuoteId($entity, $quoteId);

            if ($entity->isObjectNew()) {
                $entity->setParentId($quoteId);
                $entity->setParentEntityType(EntityData::ENTITY_TYPE_QUOTE);
            }
            $this->modelStorageQuote[$quoteId] = $entity;
        }

        return $this->modelStorageQuote[$quoteId];
    }

    /**
     * @return EntityData
     */
    protected function createEntityModel()
    {
        return $this->entityDataFactory->create();
    }
}
