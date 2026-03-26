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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Factor;

use Mirasvit\Sorting\Api\Data\RankingFactorInterface;
use Mirasvit\Sorting\Model\Indexer\FactorIndexer;
use Magento\Framework\Module\Manager as ModuleManager;

class StockQtyFactor implements FactorInterface
{
    use MappingTrait;

    const MAPPING = 'mapping';

    private $context;

    private $indexer;

    private $moduleManager;

    public function __construct(
        Context $context,
        FactorIndexer $indexer,
        ModuleManager $moduleManager
    ) {
        $this->context       = $context;
        $this->indexer       = $indexer;
        $this->moduleManager = $moduleManager;
    }

    public function getName(): string
    {
        return 'Stock Quantity';
    }

    public function getDescription(): string
    {
        return 'Rank products based on stock quantity.';
    }

    public function getUiComponent(): ?string
    {
        return 'sorting_factor_stockQty';
    }

    public function reindex(RankingFactorInterface $rankingFactor, array $productIds): void
    {
        if ($productIds) {
            return;
        }

        $mapping = $rankingFactor->getConfigData(self::MAPPING, []);

        if (
            $this->moduleManager->isEnabled('Magento_Inventory')
            && $this->moduleManager->isEnabled('Magento_InventorySales')
        ) {
            $result = $this->getInventoryStock();

            if (!count($result)) {
                $result = $this->getDefaultStock();
            }
        } else {
            $result = $this->getDefaultStock();
        } 

        $max = 0;
        foreach ($result as $row) {
            $max = max($max, $row['value']);
        }

        $this->indexer->process($rankingFactor, $productIds, function () use ($result, $mapping, $max) {
            foreach ($result as $key => $value) {
                $value = (int)$value['value'];
                $score = $this->getRangeValue($mapping, $value);

                if (count($mapping) === 0) {
                    $score = $value / $max;
                }

                $this->indexer->add((int)$key, $score, (string)$value);
            }
        });
    }

    private function getDefaultStock(): array
    {
        $resource   = $this->indexer->getResource();
        $connection = $resource->getConnection();

        $select = $connection->select();
        $select->from(
            ['e' => $resource->getTableName('catalog_product_entity')],
            ['entity_id']
        )->joinInner(
            ['stock' => $resource->getTableName('cataloginventory_stock_item')],
            'stock.product_id = e.entity_id',
            ['value' => new \Zend_Db_Expr('SUM(qty)')]
        )->group('e.entity_id');

        $stmt = $connection->query($select);

        $rows = $stmt->fetchAll(\PDO::FETCH_UNIQUE);

        // calculate factor based on child discounts
        // catalog_product_super_link - contains relations between configurable, bundled, grouped to simple
        $select = $connection->select();
        $select->from(['link' => $resource->getTableName('catalog_product_super_link')], [
            'entity_id' => 'parent_id',
        ])->joinLeft(
            ['stock' => $resource->getTableName('cataloginventory_stock_item')],
            'link.product_id = stock.product_id',
            [
                'value' => new \Zend_Db_Expr('SUM(qty)'),
            ]
        )->group('link.parent_id');

        foreach ($connection->query($select)->fetchAll(\PDO::FETCH_UNIQUE) as $key => $value) {
            $rows[$key]['value'] = $value['value'];
        };

        return $rows;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getInventoryStock(): array
    {
        $resource   = $this->indexer->getResource();
        $connection = $resource->getConnection();

        $select = $connection->select();

        $select->from(
            ['store' => $resource->getTableName('store')],
            ['website_id', 'store_id']
        )->joinInner(
            ['store_website' => $resource->getTableName('store_website')],
            'store.website_id = store_website.website_id',
            null
        )->joinInner(
            ['stock' => $resource->getTableName('inventory_stock_sales_channel')],
            'store_website.code = stock.code',
            null
        )->joinInner(
            ['source_link' => $resource->getTableName('inventory_source_stock_link')],
            'stock.stock_id = source_link.stock_id',
            ['stock_id']
        )->group('store.store_id');

        $stmt = $connection->query($select);

        foreach ($stmt->fetchAll() as $row) {

            $stockSelect = $connection->select();
            $select      = $connection->select();
            $stockId     = $row['stock_id'];

            if ($connection->isTableExists($resource->getTableName("inventory_stock_$stockId"))){
                if($connection->tableColumnExists($resource->getTableName("inventory_stock_$stockId"), 'product_id')) {
                    $stockSelect->from(
                        ['stock' => $resource->getTableName("inventory_stock_$stockId")],
                        [   
                            'entity_id' => 'product_id',
                            'value'     => new \Zend_Db_Expr('SUM(stock.quantity)')
                        ]
                    )->group('stock.product_id');

                    $stmt = $connection->query($stockSelect);

                    $rows = $stmt->fetchAll(\PDO::FETCH_UNIQUE);

                    $select->from(['link' => $resource->getTableName('catalog_product_super_link')], [
                        'entity_id' => 'parent_id',
                    ])->joinLeft(
                        ['stock' => $resource->getTableName("inventory_stock_$stockId")],
                        'link.product_id = stock.product_id',
                        ['value'   => new \Zend_Db_Expr('SUM(stock.quantity)')]
                    )->group('link.parent_id');

                } else {
                    $stockSelect->from(
                        ['stock' => $resource->getTableName("inventory_stock_$stockId")],
                    )->joinInner(
                        ['e' => $resource->getTableName('catalog_product_entity')],
                        'e.sku = stock.sku',
                        ['entity_id', 'value'   => new \Zend_Db_Expr('SUM(stock.quantity)')]
                    )->group('e.entity_id');
                    
                    $stmt = $connection->query($stockSelect);
                    $rows = [];

                    while ($row = $stmt->fetch()) {
                        $rows[$row['entity_id']] = $row;
                    }

                    $select->from(['link' => $resource->getTableName('catalog_product_super_link')], [
                        'entity_id' => 'parent_id',
                    ])->joinInner(
                        ['e' => $resource->getTableName('catalog_product_entity')],
                        'e.entity_id = link.product_id',
                        null
                    )->joinLeft(
                        ['stock' => $resource->getTableName("inventory_stock_$stockId")],
                        'e.sku = stock.sku',
                        ['value'   => new \Zend_Db_Expr('SUM(stock.quantity)')]
                    )->where(
                        'stock.quantity > 0'
                    )->group('link.parent_id');
                }
            } else {
                $stockSelect->from(
                    ['e' => $resource->getTableName('catalog_product_entity')],
                    ['entity_id']
                )->joinInner(
                    ['stock' => $resource->getTableName('cataloginventory_stock_item')],
                    'stock.product_id = e.entity_id',
                    ['value'   => new \Zend_Db_Expr('SUM(stock.qty)')]
                )->group('e.entity_id');

                $stmt = $connection->query($stockSelect);

                $rows = $stmt->fetchAll(\PDO::FETCH_UNIQUE);

                $select->from(['link' => $resource->getTableName('catalog_product_super_link')], [
                    'entity_id' => 'parent_id',
                ])->joinLeft(
                    ['stock' => $resource->getTableName('cataloginventory_stock_item')],
                    'link.product_id = stock.product_id',
                    ['value'   => new \Zend_Db_Expr('SUM(stock.qty)')]
                )->group('link.parent_id');
            }

            foreach ($connection->query($select)->fetchAll(\PDO::FETCH_UNIQUE) as $key => $value) {
                $rows[$key]['value'] = $value['value'];
            };

        }
        
        return $rows;
    }
}