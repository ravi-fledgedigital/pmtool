<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Setup;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Attribute as AttributeResource;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\Relation as RelationResource;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Relation\RelationDetails as RelationDetailsResource;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity as EntityResource;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    private const TABLE_NAMES = [
        EntityResource::TABLE_NAME,
        AttributeResource::TABLE_NAME,
        RelationResource::TABLE_NAME,
        RelationDetailsResource::TABLE_NAME,
        AttributeResource::CUSTOMER_GROUP_TABLE_NAME,
        AttributeResource::STORE_TABLE_NAME,
        'onitsukatiger_order_attribute_entity_int',
        'onitsukatiger_order_attribute_entity_decimal',
        'onitsukatiger_order_attribute_entity_datetime',
        'onitsukatiger_order_attribute_entity_text',
        'onitsukatiger_order_attribute_entity_varchar',
        AttributeResource::SHIPPING_METHODS_TABLE_NAME,
        AttributeResource::TOOLTIP_TABLE_NAME
    ];

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $connection = $setup->getConnection();

        foreach (self::TABLE_NAMES as $tableName) {
            $connection->dropTable($setup->getTable($tableName));
        }
    }
}
