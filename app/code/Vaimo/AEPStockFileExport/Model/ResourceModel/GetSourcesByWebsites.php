<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPStockFileExport\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class GetSourcesByWebsites
{
    /**
     * @var string[][]|null
     */
    private ?array $sources = null;
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return string[][]
     */
    public function execute(): array
    {
        if ($this->sources === null) {
            $connection = $this->resource->getConnection();

            $select = $connection->select()->from(
                $connection->getTableName('inventory_stock_sales_channel'),
                ['website' => 'inventory_stock_sales_channel.code']
            )->joinLeft(
                $connection->getTableName('inventory_source_stock_link'),
                'inventory_stock_sales_channel.stock_id = inventory_source_stock_link.stock_id',
                ['source' => 'inventory_source_stock_link.source_code']
            );

            $this->sources = $connection->fetchAssoc($select);
        }

        return $this->sources;
    }
}
