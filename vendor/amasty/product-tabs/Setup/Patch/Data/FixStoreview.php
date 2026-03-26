<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Setup\Patch\Data;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixStoreview implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return $this
     */
    public function apply(): self
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $this->resourceConnection->getConnection()->select()->from(
            $this->resourceConnection->getTableName(TabsInterface::STORE_TABLE_NAME),
            [TabsInterface::TAB_ID]
        )->where('store_id = 0');

        $tabIds = $connection->fetchCol($select);
        if ($tabIds) {
            $connection->delete(
                $this->resourceConnection->getTableName(TabsInterface::STORE_TABLE_NAME),
                'tab_id IN (' . implode(',', $tabIds) . ') and store_id != 0'
            );
        }

        return $this;
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
