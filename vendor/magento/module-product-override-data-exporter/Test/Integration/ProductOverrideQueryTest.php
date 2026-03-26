<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\ProductOverrideDataExporter\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for queries declared for product overrides feed
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
class ProductOverrideQueryTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Magento\QueryXml\Model\QueryFactory
     */
    private $queryFactory;

    protected function setUp(): void
    {
        $this->queryFactory = Bootstrap::getObjectManager()->create(\Magento\QueryXml\Model\QueryFactory::class);
    }

    public static function getProductOverrideQueries(): array
    {
        return [
            [
                'queryName' => 'productCategoryPermissions',
                'expectedSql' => "SELECT `catalog_product_entity`.`entity_id` AS `productId`, `catalog_product_entity`.`sku`, `magento_catalogpermissions_index_product`.`customer_group_id`, SHA1(`magento_catalogpermissions_index_product`.`customer_group_id`) AS `customerGroupCode`, `magento_catalogpermissions_index_product`.`grant_catalog_category_view` AS `displayable`, `magento_catalogpermissions_index_product`.`grant_catalog_product_price` AS `priceDisplayable`, `magento_catalogpermissions_index_product`.`grant_checkout_items` AS `addToCartAllowed`, `store_website`.`code` AS `websiteCode` "
                    . "FROM `catalog_product_entity` "
                    . "INNER JOIN `magento_catalogpermissions_index_product` ON (`magento_catalogpermissions_index_product`.`product_id` = `catalog_product_entity`.`entity_id`) "
                    . "INNER JOIN `store` ON (`store`.`store_id` = `magento_catalogpermissions_index_product`.`store_id`) "
                    . "INNER JOIN `store_website` ON (`store_website`.`website_id` = `store`.`website_id`) "
                    . "WHERE (((`catalog_product_entity`.`entity_id` IN(::entityIds::)))) AND (catalog_product_entity.created_in <= 1) AND (catalog_product_entity.updated_in > 1) "
                    . "ORDER BY `catalog_product_entity`.`entity_id` asc, `magento_catalogpermissions_index_product`.`customer_group_id` asc"
            ],
            [
                'queryName' => 'productDisplayableOverride',
                'expectedSql' => "SELECT `magento_catalogpermissions_index_product`.`product_id` AS `productId`, `magento_catalogpermissions_index_product`.`grant_catalog_category_view` AS `displayable`, `store`.`code` AS `storeViewCode` "
                    . "FROM `magento_catalogpermissions_index_product` "
                    . "INNER JOIN `store` ON (`store`.`store_id` = `magento_catalogpermissions_index_product`.`store_id` AND `store`.`code` IN(::storeViewCode::)) "
                    . "WHERE ((`magento_catalogpermissions_index_product`.`product_id` IN(::productIds::) "
                    . "AND `magento_catalogpermissions_index_product`.`customer_group_id` = ::customerGroupFilter::))"
            ],
            [
                'queryName' => 'configurableProductsByChildren',
                'expectedSql' => "SELECT `configurable_product`.`entity_id` AS `productId` FROM `catalog_product_super_link` INNER JOIN `catalog_product_entity` AS `simple_product` ON (`simple_product`.`type_id` IN('simple','virtual') AND `simple_product`.`entity_id` = `catalog_product_super_link`.`product_id`) AND (simple_product.created_in <= 1 AND simple_product.updated_in > 1) INNER JOIN `catalog_product_entity` AS `configurable_product` ON (`configurable_product`.`row_id` = `catalog_product_super_link`.`parent_id`) AND (configurable_product.created_in <= 1 AND configurable_product.updated_in > 1) WHERE ((`catalog_product_super_link`.`product_id` IN(::entityIds::))) GROUP BY `configurable_product`.`entity_id`"

            ]
        ];
    }

    /**
     * @param $queryName
     * @param $file
     * @dataProvider getProductOverrideQueries
     */
    public function testProductOverrideQueries($queryName, $expectedSql)
    {
        /** @var \Magento\Framework\App\ResourceConnection $resource */
        $resource = Bootstrap::getObjectManager()->create(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        if ($connection->isTableExists('staging_update')) {
            $sql = $this->queryFactory->create($queryName)->getSelect()->assemble();
            $actualSql = trim(str_replace(PHP_EOL, "", preg_replace("!\s+!", " ", (string) $sql)));
            self::assertEquals($expectedSql, $actualSql);
        }
    }
}
