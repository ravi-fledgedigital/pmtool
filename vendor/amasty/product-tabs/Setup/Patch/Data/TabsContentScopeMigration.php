<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Setup\Patch\Data;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Model\Source\Status;
use Amasty\CustomTabs\Model\Source\Type as TypeTab;
use Amasty\CustomTabs\Model\Tabs\ResourceModel\Collection;
use Amasty\CustomTabs\Model\Tabs\ResourceModel\CollectionFactory as TabsCollectionFactory;
use Amasty\CustomTabs\Model\Tabs\ResourceModel\Tabs;
use Amasty\CustomTabs\Model\Tabs\Tabs as TabsModel;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class TabsContentScopeMigration implements DataPatchInterface
{
    public const MIGRATION_FIELDS = [
        TabsInterface::TAB_ID,
        TabsInterface::TAB_TITLE,
        TabsInterface::TAB_NAME,
        TabsInterface::CONTENT,
        TabsInterface::STATUS
    ];

    public function __construct(
        private readonly State $state,
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly TabsCollectionFactory $tabsCollectionFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public static function getDependencies(): array
    {
        return [
            AddTabs::class
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): self
    {
        $this->state->emulateAreaCode(
            Area::AREA_ADMINHTML,
            [$this, 'migrationTabsContentToScope']
        );

        return $this;
    }

    public function migrationTabsContentToScope(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $tabsTableName = $this->moduleDataSetup->getTable(Tabs::CONTENT_TABLE_NAME);
        $collection = $this->getTabsCollection();

        foreach ($collection as $tab) {
            $tabsContentData = $this->filterTabData($tab);
            if ($this->isTabEnabledAllStoreViews($tab)) {
                $tabsContentData[TabsInterface::STATUS] = Status::ENABLED;
            }

            $connection->insertOnDuplicate(
                $tabsTableName,
                [
                    TabsInterface::STORE_ID => Store::DEFAULT_STORE_ID
                ] + $tabsContentData
            );

            foreach ($this->storeManager->getStores() as $store) {
                $storeId = (int)$store->getId();

                if (!$this->shouldDisableTabOnStoreView($tab, $storeId)) {
                    continue;
                }

                $connection->insertOnDuplicate(
                    $tabsTableName,
                    [
                        TabsInterface::TAB_ID => $tab->getId(),
                        TabsInterface::STORE_ID => $storeId,
                        TabsInterface::STATUS => Status::DISABLED
                    ]
                );
            }
        }
    }

    private function isTabEnabledAllStoreViews(TabsModel $tab): bool
    {
        $storeIds = (string)$tab->getStoreIds();

        return $storeIds !== '' && (int)$tab->getStatus() === Status::ENABLED;
    }

    private function getTabsCollection(): Collection
    {
        $storeTableName = $this->moduleDataSetup->getTable(TabsInterface::STORE_TABLE_NAME);
        $collection = $this->tabsCollectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['stores_table' => $storeTableName],
            'main_table.tab_id = stores_table.tab_id',
            []
        )
            ->columns(['store_ids' => new \Zend_Db_Expr('GROUP_CONCAT(stores_table.store_id)')])
            ->group('main_table.tab_id');

        return $collection;
    }

    private function filterTabData(TabsModel $tab): array
    {
        return array_intersect_key(
            $tab->getData(),
            array_flip(self::MIGRATION_FIELDS)
        );
    }

    private function shouldDisableTabOnStoreView(TabsModel $tab, int $storeId): bool
    {
        return $storeId !== Store::DEFAULT_STORE_ID
            && !$this->isTabEnabledOnStoreView($tab, $storeId)
            && $this->isTabEnabledAllStoreViews($tab)
            && $tab->getStoreIds() !== '0';
    }

    private function isTabEnabledOnStoreView(TabsModel $tab, int $storeId): bool
    {
        $storeIds = $tab->getStoreIds() ? array_map('intval', explode(',', $tab->getStoreIds())) : [];

        return in_array($storeId, $storeIds);
    }
}
