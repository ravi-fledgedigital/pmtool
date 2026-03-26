<?php

namespace Cpss\Crm\Model\ResourceModel\ShopReceipt\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Zend_Db;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult {

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable);
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['customer' => $this->getTable('customer_entity')],
            'main_table.member_id = customer.entity_id',
            'CONCAT(customer.firstname, " ", customer.lastname) as customer_name'
        );
        $this->getSelect()->columns([
            'total_amount_incl_tax' => '(IFNULL(main_table.total_amount, 0) + IFNULL(main_table.tax_amount, 0) + IFNULL(main_table.discount_amount, 0))',
            'discount_amount_incl_tax' => '(IFNULL(main_table.total_amount, 0) + IFNULL(main_table.tax_amount, 0))'
        ]);

        $this->addFilterToMap('customer_name', new \Zend_Db_Expr("CONCAT(customer.firstname, ' ', customer.lastname)"));
        $this->addFilterToMap('total_amount_incl_tax', new \Zend_Db_Expr("(IFNULL(main_table.total_amount, 0) + IFNULL(main_table.tax_amount, 0) + IFNULL(main_table.discount_amount, 0))"));
        $this->addFilterToMap('discount_amount_incl_tax', new \Zend_Db_Expr("(IFNULL(main_table.total_amount, 0) + IFNULL(main_table.tax_amount, 0))"));
        $this->addFilterToMap('tax_amount', new \Zend_Db_Expr("IFNULL(main_table.tax_amount, 0)"));
        $this->addFilterToMap('entity_id', new \Zend_Db_Expr("main_table.entity_id"));

        return $this;
    }
}
