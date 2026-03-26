<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Store Locator for Magento 2
 */

namespace Amasty\Storelocator\Setup\Patch\Data;

use Amasty\Storelocator\Api\Data\LocationInterface;
use Amasty\Storelocator\Model\Config\Source\ConditionType;
use Amasty\Storelocator\Model\ResourceModel\Location;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpdateConditionType implements DataPatchInterface
{
    public function __construct(
        private readonly SchemaSetupInterface $schemaSetup
    ) {
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): UpdateConditionType
    {
        $connection = $this->schemaSetup->getConnection();
        $locationTable = $this->schemaSetup->getTable(Location::TABLE_NAME);

        if (!$connection->isTableExists($locationTable)) {
            return $this;
        }

        $select = $connection->select()
            ->from($locationTable, [LocationInterface::ID])
            ->where(LocationInterface::CONDITION_TYPE . ' = ?', ConditionType::PRODUCT_ATTRIBUTE)
            ->where(LocationInterface::ACTIONS_SERIALIZED . ' = ?', Location::EMPTY_CONDITION);
        $locationIds = $connection->fetchCol($select);

        $connection->update(
            $locationTable,
            [LocationInterface::CONDITION_TYPE => ConditionType::NO_CONDITIONS],
            $connection->quoteInto(LocationInterface::ID . ' IN(?)', $locationIds)
        );

        return $this;
    }
}
