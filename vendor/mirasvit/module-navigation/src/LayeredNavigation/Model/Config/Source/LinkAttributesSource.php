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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;

class LinkAttributesSource implements OptionSourceInterface
{
    const ALL_FILTERABLE_ATTRIBUTES_VALUE = 'all_attributes';

    private $eavEntity;

    private $attributeCollection;

    public function __construct(
        Entity $eavEntity,
        Collection $attributeCollection
    ) {
        $this->eavEntity           = $eavEntity;
        $this->attributeCollection = $attributeCollection;
    }

    public function toOptionArray()
    {
        $entityTypeId = $this->eavEntity->setType('catalog_product')->getTypeId();

        $attributes = $this->attributeCollection->setEntityTypeFilter($entityTypeId)
            ->addFieldToFilter('frontend_input', ['select', 'multiselect']);

        $result = [
            [
                'label' => __('All filterable attributes'),
                'value' => self::ALL_FILTERABLE_ATTRIBUTES_VALUE
            ]
        ];

        /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute->getStoreLabel() && $attribute->getIsFilterable()) {
                $result[] = [
                    'label' => $attribute->getStoreLabel() . ' (' . $attribute->getAttributeCode() . ')',
                    'value' => $attribute->getAttributeCode(),
                ];
            }
        }

        return $result;
    }
}
