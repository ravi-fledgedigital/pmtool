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

namespace Mirasvit\Sorting\Service;

use Exception;
use Magento\Framework\App\ResourceConnection;

class PinnedProductService
{
    public const TABLE_NAME = 'mst_sorting_pinned_product';

    private $resource;

    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    public function getCategoryIds(int $productId): array
    {
        $connection = $this->resource->getConnection();
        $tableName  = $this->resource->getTableName(self::TABLE_NAME);

        $select = $connection->select()
            ->from($tableName, ['category_id'])
            ->where('product_id = ?', $productId);

        $categoryIds = $connection->fetchCol($select);

        return array_map('intval', $categoryIds);
    }

    public function getProductIds(int $categoryId): array
    {
        $connection = $this->resource->getConnection();
        $tableName  = $this->resource->getTableName(self::TABLE_NAME);

        $select = $connection->select()
            ->from($tableName, ['product_id'])
            ->where('category_id = ?', $categoryId);

        $productIds = $connection->fetchCol($select);

        return array_map('intval', $productIds);
    }

    /**
     * @throws Exception
     */
    public function saveCategoryIds(int $productId, array $categoryIds): void
    {
        $connection = $this->resource->getConnection();
        $tableName  = $this->resource->getTableName(self::TABLE_NAME);

        $connection->beginTransaction();

        try {
            $connection->delete($tableName, ['product_id = ?' => $productId]);

            if (!empty($categoryIds)) {
                $data = [];

                foreach ($categoryIds as $categoryId) {
                    $data[] = [
                        'product_id'  => $productId,
                        'category_id' => (int)$categoryId,
                    ];
                }

                $connection->insertMultiple($tableName, $data);
            }

            $connection->commit();
        } catch (Exception $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    /**
     * @throws Exception
     */
    public function saveProductIds(int $categoryId, array $productIds): void
    {
        $connection = $this->resource->getConnection();
        $tableName  = $this->resource->getTableName(self::TABLE_NAME);

        $connection->beginTransaction();

        try {
            $connection->delete($tableName, ['category_id = ?' => $categoryId]);

            if (!empty($productIds)) {
                $data = [];

                foreach ($productIds as $productId) {
                    $data[] = [
                        'product_id'  => (int)$productId,
                        'category_id' => $categoryId,
                    ];
                }

                $connection->insertMultiple($tableName, $data);
            }

            $connection->commit();
        } catch (Exception $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }
}
