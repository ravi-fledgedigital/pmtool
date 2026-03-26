<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Model\Label\Rule\Condition\Product;


use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Mirasvit\CatalogLabel\Helper\ProductData;

class AttributeSetId extends AbstractProductCondition
{
    private $eavConfig;

    private $entityAttributeSetCollectionFactory;

    public function __construct(
        Config $eavConfig,
        CollectionFactory $entityAttributeSetCollectionFactory,
        ProductData $productDataHelper
    ) {
        parent::__construct($productDataHelper);

        $this->eavConfig                           = $eavConfig;
        $this->entityAttributeSetCollectionFactory = $entityAttributeSetCollectionFactory;
    }

    public function getCode(): string
    {
        return 'attribute_set_id';
    }

    public function getLabel(): string
    {
        return (string)__('Attribute Set');
    }

    public function getValueOptions(): ?array
    {
        $entityTypeId = $this->eavConfig
            ->getEntityType('catalog_product')
            ->getId();

        return $selectOptions = $this->entityAttributeSetCollectionFactory->create()
            ->setEntityTypeFilter($entityTypeId)
            ->load()
            ->toOptionArray();
    }

    public function getInputType(): string
    {
        return self::TYPE_SELECT;
    }

    public function getValueElementType(): string
    {
        return self::TYPE_SELECT;
    }

    public function validate(AbstractModel $object, AbstractCondition $validator): bool
    {
        $attrId = $object->getAttributeSetId();

        return $validator->validateAttribute($attrId);
    }
}
