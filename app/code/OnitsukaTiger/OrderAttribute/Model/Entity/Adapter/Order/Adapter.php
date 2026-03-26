<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Entity\Adapter\Order;

use OnitsukaTiger\OrderAttribute\Api\Data\AttributeValueInterface;
use OnitsukaTiger\OrderAttribute\Model\Entity\EntityData\Converter\ConvertAttributeValue;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Adapter
{
    /**
     * @var \Magento\Sales\Api\Data\OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver
     */
    private $entityResolver;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Entity\Handler\Save
     */
    private $saveHandler;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\FormFactory
     */
    private $metadataFormFactory;

    /**
     * @var ConvertAttributeValue
     */
    private $convertAttributeValue;

    public function __construct(
        \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory,
        \OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver $entityResolver,
        \OnitsukaTiger\OrderAttribute\Model\Entity\Handler\Save $saveHandler,
        \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\FormFactory $metadataFormFactory,
        ConvertAttributeValue $convertAttributeValue
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->entityResolver = $entityResolver;
        $this->saveHandler = $saveHandler;
        $this->metadataFormFactory = $metadataFormFactory;
        $this->convertAttributeValue = $convertAttributeValue;
    }

    /**
     * @param OrderInterface $order
     * @param bool $force
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @return void
     */
    public function addExtensionAttributesToOrder(OrderInterface $order, bool $force = false): void
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if (empty($extensionAttributes)) {
            $extensionAttributes = $this->orderExtensionFactory->create();
            $order->setExtensionAttributes($extensionAttributes);
        }
        if (!$force && !empty($extensionAttributes->getOnitsukaTigerOrderAttributes())) {
            return;
        }

        $entity = $this->entityResolver->getEntityByOrder($order);
        $customAttributes = $entity->getCustomAttributes();

        if (!empty($customAttributes)) {
            $customAttributes = $this->replaceAttributeValues($customAttributes);
            $extensionAttributes->setOnitsukaTigerOrderAttributes($customAttributes);
        }
        $order->setExtensionAttributes($extensionAttributes);
        $this->setOrderData($order, $entity, $extensionAttributes->getOnitsukaTigerOrderAttributes());
    }

    /**
     * @param OrderInterface $order
     * @throws LocalizedException
     * @throws CouldNotSaveException
     * @return void
     */
    public function saveOrderValues(OrderInterface $order): void
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getOnitsukaTigerOrderAttributes()) {
            $entity = $this->entityResolver->getEntityByOrder($order);
            $attributes = $extensionAttributes->getOnitsukaTigerOrderAttributes();
            $entityType = $entity->getParentEntityType();
            $parentId = $entity->getParentId();
            $entityId = $entity->getEntityId();
            $entity->unsetData();
            $entity->setParentEntityType($entityType);
            $entity->setParentId($parentId);
            $entity->setEntityId($entityId);
            $entity->setCustomAttributes($attributes);
            $this->setOrderData($order, $entity, $attributes);
            $this->saveHandler->execute($entity);
        }
    }

    /**
     * @param OrderInterface $order
     * @param \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entity
     * @param \Magento\Framework\Api\AttributeValue[] $attributes
     */
    private function setOrderData(
        OrderInterface $order,
        \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entity,
        $attributes
    ) {
        if (!is_array($attributes)) {
            return;
        }
        $form = $this->createEntityForm($entity);
        $data = $form->outputData();

        foreach ($attributes as $orderAttribute) {
            $attributeCode = $orderAttribute->getAttributeCode();
            if (!empty($data[$attributeCode])) {
                $order->setData($attributeCode, $data[$attributeCode]);
            }
        }
    }

    /**
     * Return Form instance
     *
     * @param \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entity
     *
     * @return \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form
     */
    protected function createEntityForm($entity)
    {
        /** @var \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form $formProcessor */
        $formProcessor = $this->metadataFormFactory->create();
        $formProcessor->setFormCode('all_attributes')
            ->setEntity($entity)
            ->setInvisibleIgnored(false);

        return $formProcessor;
    }

    /**
     * @param AttributeInterface[] $attributeValues
     * @return AttributeValueInterface[]
     */
    private function replaceAttributeValues(array $attributeValues): array
    {
        $result = [];
        foreach ($attributeValues as $attributeValue) {
            $result[] = $this->convertAttributeValue->execute($attributeValue);
        }

        return $result;
    }
}
