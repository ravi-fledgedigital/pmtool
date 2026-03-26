<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Setup;

use Amasty\Rma\Model\Chat\ResourceModel\Message;
use Amasty\Rma\Model\Chat\ResourceModel\MessageFile;
use Amasty\Rma\Model\Condition\ResourceModel\Condition;
use Amasty\Rma\Model\Condition\ResourceModel\ConditionStore;
use Amasty\Rma\Model\History\ResourceModel\History;
use Amasty\Rma\Model\Reason\ResourceModel\Reason;
use Amasty\Rma\Model\Reason\ResourceModel\ReasonStore;
use Amasty\Rma\Model\Request\ResourceModel\GuestCreateRequest;
use Amasty\Rma\Model\Request\ResourceModel\Request;
use Amasty\Rma\Model\Request\ResourceModel\RequestItem;
use Amasty\Rma\Model\Request\ResourceModel\Tracking;
use Amasty\Rma\Model\Resolution\ResourceModel\Resolution;
use Amasty\Rma\Model\Resolution\ResourceModel\ResolutionStore;
use Amasty\Rma\Model\ReturnRules\ResourceModel\ReturnRules;
use Amasty\Rma\Model\ReturnRules\ResourceModel\ReturnRulesCustomerGroups;
use Amasty\Rma\Model\ReturnRules\ResourceModel\ReturnRulesResolutions;
use Amasty\Rma\Model\ReturnRules\ResourceModel\ReturnRulesWebsites;
use Amasty\Rma\Model\Status\ResourceModel\Status;
use Amasty\Rma\Model\Status\ResourceModel\StatusStore;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this
            ->uninstallTables($setup)
            ->uninstallConfigData($setup);
    }

    private function uninstallTables(SchemaSetupInterface $setup): self
    {
        $tablesToDrop = [
            Message::TABLE_NAME,
            MessageFile::TABLE_NAME,
            Condition::TABLE_NAME,
            ConditionStore::TABLE_NAME,
            History::TABLE_NAME,
            Reason::TABLE_NAME,
            ReasonStore::TABLE_NAME,
            GuestCreateRequest::TABLE_NAME,
            Request::TABLE_NAME,
            RequestItem::TABLE_NAME,
            Tracking::TABLE_NAME,
            Resolution::TABLE_NAME,
            ResolutionStore::TABLE_NAME,
            ReturnRules::TABLE_NAME,
            ReturnRulesCustomerGroups::TABLE_NAME,
            ReturnRulesResolutions::TABLE_NAME,
            ReturnRulesWebsites::TABLE_NAME,
            Status::TABLE_NAME,
            StatusStore::TABLE_NAME
        ];
        foreach ($tablesToDrop as $table) {
            $setup->getConnection()->dropTable(
                $setup->getTable($table)
            );
        }

        return $this;
    }

    private function uninstallConfigData(SchemaSetupInterface $setup): self
    {
        $configTable = $setup->getTable('core_config_data');
        $setup->getConnection()->delete($configTable, "`path` LIKE 'amrma%'");

        return $this;
    }
}
