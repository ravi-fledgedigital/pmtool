<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Entity\Adapter;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity as EntityResource;

class AbstractConverter
{
    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Entity\EntityDataFactory
     */
    private $entityDataFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var \Magento\Framework\Api\AttributeValueFactory
     */
    private $attributeValueFactory;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Entity\Handler\Save
     */
    protected $saveHandler;

    public function __construct(
        \OnitsukaTiger\OrderAttribute\Model\Entity\EntityDataFactory $entityDataFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\Api\AttributeValueFactory $attributeValueFactory,
        \OnitsukaTiger\OrderAttribute\Model\Entity\Handler\Save $saveHandler
    ) {
        $this->entityDataFactory = $entityDataFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeValueFactory = $attributeValueFactory;
        $this->saveHandler = $saveHandler;
    }

    /**
     * Extract attribute values from $object
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @param bool                          $ignoreNull
     *
     * @return \Magento\Framework\Api\AttributeValue[]
     */
    public function convertToInterface(\Magento\Framework\DataObject $object, $ignoreNull = false)
    {
        $customAttributes = [];
        $attributeCodes = $this->eavConfig->getEntityAttributeCodes(EntityResource::ENTITY_TYPE_CODE, $object);
        foreach ($attributeCodes as $attributeCode) {
            $value = $object->getData($attributeCode);
            if ($ignoreNull && $value === null) {
                continue;
            }
            $customAttributes[$attributeCode] = $this->attributeValueFactory->create()
                ->setAttributeCode($attributeCode)
                ->setValue($value);
        }

        return $customAttributes;
    }

    /**
     * @return \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData
     */
    protected function createEntityModel()
    {
        return $this->entityDataFactory->create();
    }
}
