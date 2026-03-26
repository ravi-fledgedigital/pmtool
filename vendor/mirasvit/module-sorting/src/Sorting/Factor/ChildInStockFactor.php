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
use Mirasvit\Core\Service\CompatibilityService;
use Magento\Framework\Module\Manager as ModuleManager;

class ChildInStockFactor implements FactorInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var FactorIndexer
     */
    protected $indexer;

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
        return 'Number of Child Products in Stock';
    }

    public function getDescription(): string
    {
        return 'Rank products based on the number of child products available in stock';
    }

    public function getUiComponent(): ?string
    {
        return null;
    }

    public function reindex(RankingFactorInterface $rankingFactor, array $productIds): void
    {
        if ($productIds) {
            return;
        }

        if (
            $this->moduleManager->isEnabled('Magento_Inventory')
            && $this->moduleManager->isEnabled('Magento_InventorySales')
        ) {
            $result = $this->getMsiStock();
        } else {
            $result = $this->getDefaultStock();
        }

        $this->indexer->process($rankingFactor, $productIds, function () use ($result) {
            foreach ($result as $row) {
                $this->indexer->add(
                    (int)$row['entity_id'],
                    (int)$row['value'],
                    (string)$row['details'],
                    (int)($row['store_id'] ?? 0)
                );
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
            [
                'value'   => new \Zend_Db_Expr('COUNT(stock.product_id)'),
                'details' => new \Zend_Db_Expr( 'CONCAT_WS(" ", "Product ID in stock:", GROUP_CONCAT(stock.product_id))')
            ]
        )->where(
            'stock.qty > 0'
        )->group('e.entity_id');

        $rows = $connection->query($select)->fetchAll(\PDO::FETCH_UNIQUE);

        $select = $connection->select();
        $select->from(
            ['e' => $resource->getTableName('catalog_product_entity')],
            ['entity_id']
        )->joinInner(
            ['link' => $resource->getTableName('catalog_product_super_link')],
            CompatibilityService::isEnterprise()
                ? 'e.row_id = link.parent_id'
                : 'e.entity_id = link.parent_id',
        )->joinLeft(
            ['stock' => $resource->getTableName('cataloginventory_stock_item')],
            'link.product_id = stock.product_id',
            [
                'value'   => new \Zend_Db_Expr('COUNT(stock.product_id)'),
                'details' => new \Zend_Db_Expr( 'CONCAT_WS(" ", "Child products IDs in stock:", GROUP_CONCAT(stock.product_id))')
            ]
        )->where(
            'stock.qty > 0'
        )->group('link.parent_id');

        foreach ($connection->query($select)->fetchAll(\PDO::FETCH_UNIQUE) as $key => $value) {
            $rows[$key]['value']   = $value['value'];
            $rows[$key]['details'] = $value['details'];
        };

        $result = [];

        foreach ($rows as $entityId => $data) {
            $result[] = [
                'entity_id' => $entityId,
                'value'     => $data['value'],
                'details'   => $data['details'],
            ];
        }

        return $result;
    }

    private function getMsiStock(): array
    {
        $resource   = $this->indexer->getResource();
        $connection = $resource->getConnection();

        $storeStockMap = $this->getStoreStockMap($connection, $resource);

        if (!$storeStockMap) {
            return $this->getDefaultStock();
        }

        $result = [];

        foreach ($storeStockMap as $mapping) {
            $storeId    = (int)$mapping['store_id'];
            $stockId    = (int)$mapping['stock_id'];
            $stockTable = $resource->getTableName("inventory_stock_$stockId");

            if (!$connection->isTableExists($stockTable)) {
                continue;
            }

            $hasProductId = $connection->tableColumnExists($stockTable, 'product_id');

            $rows = $this->getMsiSimpleProducts($connection, $resource, $stockTable, $hasProductId);

            foreach ($this->getMsiConfigurableProducts($connection, $resource, $stockTable, $hasProductId) as $key => $value) {
                $rows[$key]['value']   = $value['value'];
                $rows[$key]['details'] = $value['details'];
            }

            foreach ($rows as $entityId => $data) {
                $result[] = [
                    'entity_id' => $entityId,
                    'value'     => $data['value'],
                    'details'   => $data['details'],
                    'store_id'  => $storeId,
                ];
            }
        }

        if (!$result) {
            return $this->getDefaultStock();
        }

        return $result;
    }

    private function getStoreStockMap($connection, $resource): array
    {
        $channelTable = $resource->getTableName('inventory_stock_sales_channel');

        if (!$connection->isTableExists($channelTable)) {
            return [];
        }

        $select = $connection->select();
        $select->from(
            ['store' => $resource->getTableName('store')],
            ['store_id']
        )->joinInner(
            ['store_website' => $resource->getTableName('store_website')],
            'store.website_id = store_website.website_id',
            null
        )->joinInner(
            ['channel' => $channelTable],
            'store_website.code = channel.code',
            ['stock_id']
        )->where(
            'store.store_id > 0'
        )->group('store.store_id');

        return $connection->query($select)->fetchAll();
    }

    private function getMsiSimpleProducts($connection, $resource, string $stockTable, bool $hasProductId): array
    {
        $select = $connection->select();

        if ($hasProductId) {
            $select->from(
                ['stock' => $stockTable],
                ['product_id']
            )->columns([
                'value'   => new \Zend_Db_Expr('COUNT(stock.product_id)'),
                'details' => new \Zend_Db_Expr('CONCAT_WS(" ", "Product ID in stock:", GROUP_CONCAT(stock.product_id))'),
            ])->where(
                'stock.is_salable = 1'
            )->group('stock.product_id');
        } else {
            $select->from(
                ['stock' => $stockTable],
                []
            )->joinInner(
                ['e' => $resource->getTableName('catalog_product_entity')],
                'e.sku = stock.sku',
                [
                    'entity_id',
                    'value'   => new \Zend_Db_Expr('COUNT(e.entity_id)'),
                    'details' => new \Zend_Db_Expr('CONCAT_WS(" ", "Product ID in stock:", GROUP_CONCAT(e.entity_id))'),
                ]
            )->where(
                'stock.is_salable = 1'
            )->group('e.entity_id');
        }

        return $connection->query($select)->fetchAll(\PDO::FETCH_UNIQUE);
    }

    private function getMsiConfigurableProducts($connection, $resource, string $stockTable, bool $hasProductId): array
    {
        $select = $connection->select();

        $select->from(
            ['e' => $resource->getTableName('catalog_product_entity')],
            ['entity_id']
        )->joinInner(
            ['link' => $resource->getTableName('catalog_product_super_link')],
            CompatibilityService::isEnterprise()
                ? 'e.row_id = link.parent_id'
                : 'e.entity_id = link.parent_id',
        );

        if ($hasProductId) {
            $select->joinLeft(
                ['stock' => $stockTable],
                'link.product_id = stock.product_id',
                [
                    'value'   => new \Zend_Db_Expr('COUNT(stock.product_id)'),
                    'details' => new \Zend_Db_Expr('CONCAT_WS(" ", "Child products IDs in stock:", GROUP_CONCAT(stock.product_id))'),
                ]
            );
        } else {
            $select->joinInner(
                ['child' => $resource->getTableName('catalog_product_entity')],
                'child.entity_id = link.product_id',
                []
            )->joinLeft(
                ['stock' => $stockTable],
                'child.sku = stock.sku',
                [
                    'value'   => new \Zend_Db_Expr('COUNT(child.entity_id)'),
                    'details' => new \Zend_Db_Expr('CONCAT_WS(" ", "Child products IDs in stock:", GROUP_CONCAT(child.entity_id))'),
                ]
            );
        }

        $select->where(
            'stock.is_salable = 1'
        )->group('link.parent_id');

        return $connection->query($select)->fetchAll(\PDO::FETCH_UNIQUE);
    }
}
