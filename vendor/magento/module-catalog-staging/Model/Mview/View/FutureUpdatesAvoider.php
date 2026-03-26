<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model\Mview\View;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\Mview\View\SubscriptionStatementPostprocessorInterface;

/**
 * Update trigger statement to prevent adding to cl future update entities.
 */
class FutureUpdatesAvoider implements SubscriptionStatementPostprocessorInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function process(string $tableName, string $event, string $statement): string
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName($tableName);
        if ($connection->isTableExists($tableName)) {
            $columns = $connection->describeTable($tableName);
            if (isset($columns['created_in'])) {
                switch ($event) {
                    case Trigger::EVENT_DELETE:
                        $condition = 'OLD.created_in <= UNIX_TIMESTAMP()';
                        break;
                    default:
                        $condition = 'NEW.created_in <= UNIX_TIMESTAMP()';
                }
                $statement = sprintf('IF (%s) THEN %s END IF;', $condition, $statement);
            }
        }

        return $statement;
    }
}
