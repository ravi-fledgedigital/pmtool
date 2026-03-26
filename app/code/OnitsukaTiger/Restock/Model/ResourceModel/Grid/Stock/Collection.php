<?php
/** phpcs:ignoreFile */
namespace OnitsukaTiger\Restock\Model\ResourceModel\Grid\Stock;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface;

/**
 *  Collection Class
 */
class Collection extends SearchResult
{
    protected $coreSession;

    protected $request;

    protected $localeDate;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        RequestInterface $request,
        SessionManagerInterface $coreSession,
        TimezoneInterface $localeDate,
        $mainTable,
        $resourceModel
    ) {
        $this->request = $request;
        $this->coreSession = $coreSession;
        $this->localeDate = $localeDate;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->addFilterToMap('fullname', new \Zend_Db_Expr("CONCAT(firstname, ' ', lastname)"));
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $params = $this->request->getParams();
        $id = !empty($params['id']) ? $params['id'] : $this->coreSession->getRestockProductId();

        $this->getSelect()->distinct(true)
            ->joinLeft(
                ['customer' => $this->getTable('customer_entity')],
                'main_table.customer_id = customer.entity_id',
                [
                    'fullname' => new \Zend_Db_Expr("TRIM(REPLACE(CONCAT(customer.firstname, ' ', customer.lastname), '&nbsp;', ''))"),
                    'email'
                ]
            )
            ->joinLeft(
                ['product' => $this->getTable('product_alert_stock_grid')],
                'main_table.product_id = product.product_id AND product.store_id = main_table.store_id',
                ['product_price' => new \Zend_Db_Expr("FORMAT(product.product_price, 2)")]
            )
            ->where("main_table.product_id = ?", $id);

       // echo $this->getSelect();
        //exit;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'add_date' || $field === 'send_date') {
            if (is_array($condition)) {
                foreach ($condition as $key => $value) {
                    if (!is_object($value) && strpos($value, '-') !== false) {
                        $condition[$key] = $this->localeDate->convertConfigTimeToUtc($value);
                    }
                }
            }
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
