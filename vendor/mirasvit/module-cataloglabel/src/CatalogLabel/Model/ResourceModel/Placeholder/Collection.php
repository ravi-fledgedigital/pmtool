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


namespace Mirasvit\CatalogLabel\Model\ResourceModel\Placeholder;


use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;


/**
 *
 * @SuppressWarnings(PHPMD)
 */
class Collection extends AbstractCollection implements ArrayInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'placeholder_id';//@codingStandardsIgnoreLine

    protected $entityFactory;

    protected $logger;

    protected $fetchStrategy;

    protected $eventManager;

    protected $storeManager;

    protected $connection;

    protected $resource;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        ?AdapterInterface $connection = null,
        ?AbstractDb $resource = null
    ) {
        $this->entityFactory = $entityFactory;
        $this->logger = $logger;
        $this->fetchStrategy = $fetchStrategy;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->connection = $connection;
        $this->resource = $resource;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    protected function _construct()
    {
        $this->_init(
            'Mirasvit\CatalogLabel\Model\Placeholder',
            'Mirasvit\CatalogLabel\Model\ResourceModel\Placeholder'
        );
    }

    public function toOptionArray(): array
    {
        return $this->_toOptionArray('placeholder_id', 'name');
    }
}
