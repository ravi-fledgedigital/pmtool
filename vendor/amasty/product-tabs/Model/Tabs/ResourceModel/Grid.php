<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs\ResourceModel;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\DB\Helper;

class Grid extends SearchResult
{
    use CollectionTrait;

    /**
     * @var Helper
     */
    protected $dbHelper;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        Helper $dbHelper,
        $mainTable = Tabs::TABLE_NAME,
        $resourceModel = Tabs::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->dbHelper = $dbHelper;
    }

    /**
     * Set resource model and determine field mapping
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_map['fields']['stores'] = 'stores_table.store_id';
        $this->_map['fields']['tab_id'] = 'main_table.tab_id';
        parent::_construct();
    }
}
