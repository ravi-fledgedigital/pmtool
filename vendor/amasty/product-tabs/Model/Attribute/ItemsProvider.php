<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Attribute;

use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

class ItemsProvider
{
    public const EXCLUDE_ATTRS = [
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout',
        'custom_layout_update',
        'msrp_display_actual_price_type',
        'options_container',
        'price',
        'price_type',
        'price_view',
        'tier_price',
        'visibility',
        'quantity_and_stock_status',
        'page_layout',
        'gift_message_available',
        'url_key'
    ];

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function execute()
    {
        $attributesData = [];
        foreach ($this->getAttributeItems() as $attribute) {
            $attributesData[$attribute->getAttributeCode()] = [
                'code' => $attribute->getAttributeCode(),
                'label' => $attribute->getFrontendLabel()
            ];
        }

        return $attributesData;
    }

    /**
     * @return \Magento\Framework\DataObject[]
     */
    protected function getAttributeItems()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(Set::KEY_ENTITY_TYPE_ID, 4)
            ->addVisibleFilter()
            ->addFieldToFilter('main_table.frontend_input', ['neq' => 'price'])
            ->addFieldToFilter('main_table.attribute_code', ['nin' => self::EXCLUDE_ATTRS]);

        return $collection->getItems();
    }
}
