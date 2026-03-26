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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Model\ResourceModel\Label;


use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\Core\Model\Date;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;


/**
 *
 * @SuppressWarnings(PHPMD)
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'label_id';//@codingStandardsIgnoreLine

    protected $storeManager;

    protected $entityFactory;

    protected $logger;

    protected $fetchStrategy;

    protected $eventManager;

    protected $connection;

    protected $resource;

    public function __construct(
        StoreManagerInterface $storeManager,
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        ?AdapterInterface $connection = null,
        ?AbstractDb $resource = null
    ) {
        $this->storeManager  = $storeManager;
        $this->entityFactory = $entityFactory;
        $this->logger        = $logger;
        $this->fetchStrategy = $fetchStrategy;
        $this->eventManager  = $eventManager;
        $this->connection    = $connection;
        $this->resource      = $resource;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    protected function _construct()
    {
        $this->_init('Mirasvit\CatalogLabel\Model\Label', 'Mirasvit\CatalogLabel\Model\ResourceModel\Label');
    }

    public function addActiveFilter(): self
    {
        $date = new Date();

        $activeFrom   = [];
        $activeFrom[] = ['date' => true, 'to' => date($date->toString('YYYY-MM-dd H:mm:ss'))];
        $activeFrom[] = ['date' => true, 'null' => true];

        $activeTo   = [];
        $activeTo[] = ['date' => true, 'from' => date($date->toString('YYYY-MM-dd H:mm:ss'))];
        $activeTo[] = ['date' => true, 'null' => true];

        $this->addFieldToFilter(LabelInterface::IS_ACTIVE, 1);
        $this->addFieldToFilter(LabelInterface::ACTIVE_FROM, $activeFrom);
        $this->addFieldToFilter(LabelInterface::ACTIVE_TO, $activeTo);

        return $this;
    }

    public function addCustomerGroupFilter(int $groupId): self
    {
        $this->addFieldToFilter(LabelInterface::CUSTOMER_GROUP_IDS, ['finset' => $groupId]);

        return $this;
    }

    public function addStoreFilter(int $store): self
    {
        if (!$this->storeManager->isSingleStoreMode()) {
            $this->addFieldToFilter(
                [LabelInterface::STORE_IDS,LabelInterface::STORE_IDS],
                [
                    ['finset' => 0],
                    ['finset' => $store]
                ]
            );
        }

        return $this;
    }
}
