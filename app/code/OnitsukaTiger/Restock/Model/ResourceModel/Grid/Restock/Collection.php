<?php
namespace OnitsukaTiger\Restock\Model\ResourceModel\Grid\Restock;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Session\SessionManagerInterface;
use OnitsukaTiger\Favorite\Helper\Data as DataHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Order grid collection
 */
class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected $dataHelper;
	protected $_coreSession;
    protected $request;
    protected $localeDate;

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable ,
        $resourceModel,
        DataHelper $dataHelper,
        SessionManagerInterface $coreSession,
        Http $request,
        TimezoneInterface $localeDate
    ) {
        $this->dataHelper = $dataHelper;
		$this->_coreSession = $coreSession;
        $this->request = $request;
        $this->localeDate = $localeDate;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->addFilterMapColumn();
    }

    /**
     * @inheritdoc
     *
     * @since PHP_TASK-585 - Added a where clause to `restock_total` above 0
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $attriArr = $this->dataHelper->getAttributeIdsByAttributeCodes(DataHelper::PRODUCT_COL);
        $cataloginventory = $this->dataHelper->getTableName('cataloginventory_stock_item');
        $varchar = $this->dataHelper->getTableName('catalog_product_entity_varchar');
        $decimal = $this->dataHelper->getTableName('catalog_product_entity_decimal');
        $params = $this->request->getParams();

        $dateFrom = '2001-01-01 00:00:00';
        if(!empty($params['filters']['period']['from'])){
            $dateFrom = $this->dataHelper->formatDate($params['filters']['period']['from'], 'from');
            $dateFrom = $this->localeDate->convertConfigTimeToUtc($dateFrom);
        }

        $dateTo = $this->dataHelper->getCurrentDay();
        if(!empty($params['filters']['period']['to'])){
            $dateTo = $this->dataHelper->formatDate($params['filters']['period']['to'], 'to');
            $dateTo = $this->localeDate->convertConfigTimeToUtc($dateTo);
        }

        $this->getSelect()
        ->joinLeft(
            [
                'qty' => $cataloginventory
            ],
            'main_table.entity_id = qty.product_id',
            ['qty.qty as qty']
        )
        ->where('qty.stock_id = 1')
        ->joinLeft(
            [
                'productname' => $varchar
            ],
            'main_table.entity_id = productname.row_id',
            ['productname.value as name']
        )
        ->where('productname.attribute_id = ?', $attriArr['name'])
        ->joinLeft(
            [
                'price' => $decimal
            ],
            'main_table.entity_id = price.row_id',
            ['price.value as price']
        )
        ->where('price.attribute_id = ?', $attriArr['price']);
        /*->joinLeft(
            [
                'thumbnail' => $varchar
            ],
            'main_table.entity_id = thumbnail.row_id',
            ['thumbnail.value as thumbnail']
        )
        ->where('thumbnail.attribute_id = ?', $attriArr['thumbnail']);*/

        $this->getSelect()->joinLeft(
            ['alert' => $this->getTable('product_alert_stock')],
            'qty.product_id = alert.product_id',
            ['product_id' => 'alert.product_id']
        );
        $this->getSelect()->joinLeft(
            ['customer' => $this->getTable('customer_entity')],
            'alert.customer_id = customer.entity_id',
            ['customer_id' => 'alert.customer_id']
        );

        $this->getSelect()->columns("(
            SELECT
                DATE_FORMAT(max(pas.send_date), '%M %d, %Y %h:%i:%s %p')
            FROM
                product_alert_stock AS pas
            WHERE
                (
                    main_table.entity_id = pas.product_id AND
                    pas.add_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                )
        ) AS restock_email_sent");

        $this->getSelect()->columns("(
            SELECT
                COUNT(*)
            FROM
                product_alert_stock AS pas
            WHERE
                (
                    main_table.entity_id = pas.product_id AND
                    pas.add_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                )
        ) AS restock_total");

        $this->getSelect()->columns("(
            SELECT
                COUNT(*)
            FROM
                product_alert_stock AS pas
            WHERE
                (
                    main_table.entity_id = pas.product_id AND
                    pas.status = 1 AND
                    pas.add_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                )
        ) AS restock_sent");

        $this->getSelect()->columns("(
            SELECT
                COUNT(*)
            FROM
                product_alert_stock AS pas
            WHERE
                (
                    main_table.entity_id = pas.product_id AND
                    pas.status = 0 AND
                    pas.add_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                )
        ) AS restock_not_sent");

        $this->getSelect()->where(new \Zend_Db_Expr("
            (
                SELECT
                    COUNT(0)
                FROM
                    product_alert_stock AS pas
                WHERE
                    (
                        main_table.entity_id = pas.product_id AND
                        pas.add_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    )
            ) > 0
        "));

        $this->getSelect()->group(['main_table.entity_id']);
        
        return $this;
    }


    public function addFilterMapColumn()
    {
        $columnsToMap = [
            [
                "alias"     => "name",
                "fields"    => new \Zend_Db_Expr('productname.value')
            ],
            [
                "alias"     => "sku",
                "fields"    => new \Zend_Db_Expr('main_table.sku')
            ],
            [
                "alias"     => "type_id",
                "fields"    => new \Zend_Db_Expr('main_table.type_id')
            ],
            [
                "alias"     => "entity_id",
                "fields"    => new \Zend_Db_Expr('main_table.entity_id')
            ]
        ];

        foreach($columnsToMap as $columnIndex){
            $this->addFilterToMap(
                $columnIndex['alias'],
                $columnIndex['fields']
            );
        }
    }


    protected function _renderFiltersBefore() {
        $filters = $this->request->getParams();
        if (!empty($filters['search'])) {
            $typeID = strtolower($filters['search']) === 'simple product' ? 'simple' : (strtolower($filters['search']) === 'configurable product' ? 'configurable' : $filters['search']);
            $this->getSelect()->where(new \Zend_Db_Expr("
                main_table.entity_id LIKE '%" . $filters['search'] . "%'
                OR productname.value LIKE '%" . $filters['search'] . "%'
                OR main_table.type_id LIKE '%" . $typeID . "%'
                OR main_table.sku LIKE '%" . $filters['search'] . "%'
                OR price.value LIKE '%" . $filters['search'] . "%'
                OR qty.qty LIKE '%" . $filters['search'] . "%'
            "));
        }

        parent::_renderFiltersBefore();
    }
}
