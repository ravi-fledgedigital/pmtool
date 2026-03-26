<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\Favorite\Model\ResourceModel\Listing\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Session\SessionManagerInterface;
use OnitsukaTiger\Favorite\Helper\Data as DataHelper;
use Magento\Framework\App\Request\Http;

/**
 * Order grid collection
 */
class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected $dataHelper;
	protected $_coreSession;
    protected $request;
    
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
        DataHelper $dataHelper,
        SessionManagerInterface $coreSession,
        Http $request,
        $resourceModel = \Magento\Wishlist\Model\ResourceModel\Item::class,
        $mainTable = 'wishlist_item'
    ) {
        $this->dataHelper = $dataHelper;
		$this->_coreSession = $coreSession;
        $this->request = $request;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->addFilterMapColumn();
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        // parent::_initSelect();
        $attriArr = $this->dataHelper->getAttributeIdsByAttributeCodes(DataHelper::PRODUCT_COL);
        //$sizeId = $attriArr['size'];

        $cataloginventory = $this->dataHelper->getTableName('cataloginventory_stock_item');
        $varchar = $this->dataHelper->getTableName('catalog_product_entity_varchar');
        $decimal = $this->dataHelper->getTableName('catalog_product_entity_decimal');
        $product = $this->dataHelper->getTableName('catalog_product_entity');

        $params = $this->request->getParams();
        $dateFrom = '2000-01-01 0:00:00';
        $dateTo = '2999-01-01 00:00:00';

        if(!empty($params['filters']['period']['from'])){
            $dateFrom = $this->dataHelper->formatDate($params['filters']['period']['from'], 'from');
        }

        if(!empty($params['filters']['period']['to'])){
            $dateTo = $this->dataHelper->formatDate($params['filters']['period']['to'], 'to');
        }


        $this->getSelect()->from(['main_table' => new \Zend_Db_Expr("(
            SELECT *, 
                CASE
                    WHEN base_wli.product_id != entity_id
                    THEN entity_id
                    ELSE
                    IFNULL((SELECT entity_id FROM catalog_product_relation, catalog_product_entity, catalog_product_entity_varchar
                    WHERE
                    parent_id = base_wli.product_id
                    AND child_id = catalog_product_entity.entity_id
                    AND child_id = catalog_product_entity_varchar.row_id
                    AND sku like (SELECT concat((select sku from catalog_product_entity where entity_id = base_wli.product_id), '_', color_code, '_%'))
                    AND attribute_id = ".$attriArr['thumbnail']." limit 1
                    ), base_wli.product_id)
                END
                    AS thumbnail_id
            FROM
                (SELECT
                    ANY_VALUE(product_id) AS product_id,
                    ANY_VALUE(inner_base.new_product_id) AS entity_id,
                    ANY_VALUE(inner_base.color_code) AS color_code,
                    COUNT(inner_base.new_product_id) AS total_favorites
                FROM   
                        (SELECT
                            *,
                            (
                                CASE 
                                WHEN json_length(base_wlio.wlia_value) = 1
                                    THEN inner_wli.product_id
                                    ELSE (SELECT product_id FROM wishlist_item_option WHERE code = 'simple_product' AND wishlist_item_id = inner_wli.wishlist_item_id)
                                END
                            ) AS new_product_id,
                            (select value from eav_attribute_option_value where
                            option_id = json_unquote(json_extract(base_wlio.wlia_value, concat('$.',(SELECT attribute_id FROM eav_attribute WHERE entity_type_id=4 AND attribute_code='color_code'))))
                            and store_id = (select store_id from store where code = 'default'))
                            as color_code
                        FROM
                            wishlist_item AS inner_wli
                        LEFT JOIN(
                            SELECT
                                product_id as wlia_product_id,
                                wishlist_item_id as wlia_wishlist_item_id,
                                value as wlia_value
                            FROM
                                wishlist_item_option as inner_wlio
                            WHERE
                                code = 'attributes'
                        ) base_wlio ON inner_wli.wishlist_item_id = base_wlio.wlia_wishlist_item_id
                        WHERE
                            added_at >= '" . $dateFrom . "' AND added_at <= '" . $dateTo . "'
                    ) AS inner_base
                GROUP BY
                inner_base.new_product_id,color_code
            ) AS base_wli
            
        )")]);

        $this->getSelect()
        ->joinLeft(
            [
                'product' => $product
            ],
            'main_table.entity_id = product.entity_id',
            ['product.type_id as type_id']
        )
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
                'prodname' => $varchar
            ],
            'main_table.entity_id = prodname.row_id',
            ['prodname.value as name']
        )
        ->where('prodname.attribute_id = ?', $attriArr['name'])
        ->joinLeft(
            [
                'price' => $decimal
            ],
            'main_table.entity_id = price.row_id',
            ['price.value as price']
        )
        ->where('price.attribute_id = ?', $attriArr['price'])
        ->joinLeft(
            [
                'thumbnail' => $varchar
            ],
            'main_table.thumbnail_id = thumbnail.row_id',
            ['thumbnail.value as thumbnail']
        )
        ->where('thumbnail.attribute_id = ?', $attriArr['thumbnail']);

        $this->getSelect()->columns([
            "sku" => new \Zend_Db_Expr("(CASE WHEN type_id = 'configurable'
                    THEN concat(concat(`product`.`sku`,'_'), `main_table`.`color_code`)
                    ELSE `product`.`sku`
                    END)"),
            "fku_id" => new \Zend_Db_Expr("(CASE WHEN type_id = 'configurable'
                THEN concat(concat(`main_table`.`entity_id`,'_'), `main_table`.`color_code`)
                ELSE `main_table`.`entity_id`
                END)"),
            "display_id" => new \Zend_Db_Expr("(CASE WHEN type_id = 'configurable'
                THEN ''
                ELSE `main_table`.`entity_id`
                END)")
                ,
            "display_type_id" => new \Zend_Db_Expr("(CASE WHEN type_id = 'configurable'
                THEN ''
                ELSE `product`.`type_id`
                END)")
        ]);

        $this->getSelect()->distinct(true);
        //$this->getSelect()->group(new \Zend_Db_Expr('main_table.entity_id, main_table.color_code'));
        
        return $this;
    }

    public function addFilterMapColumn()
    {
        $columnsToMap = [
            [
                "alias"     => "name", 
                "fields"    => new \Zend_Db_Expr('prodname.value')
            ],
            [
                "alias"     => "sku", 
                "fields"    => new \Zend_Db_Expr("(CASE WHEN type_id = 'configurable'
                    THEN concat(concat(`product`.`sku`,'_'), `main_table`.`color_code`)
                    ELSE `product`.`sku`
                    END)")
            ],
            [
                "alias"     => "fku_id", 
                "fields"    => new \Zend_Db_Expr("(CASE WHEN type_id = 'configurable'
                    THEN concat(concat(`main_table`.`entity_id`,'_'), `main_table`.`color_code`)
                    ELSE `main_table`.`entity_id`
                    END)")
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
                OR prodname.value LIKE '%" . $filters['search'] . "%' 
                OR product.type_id LIKE '%" . $typeID . "%' 
                OR product.sku LIKE '%" . $filters['search'] . "%' 
                OR concat(concat(`product`.`sku`,'_'), `main_table`.`color_code`) LIKE '%" . $filters['search'] . "%' 
                OR price.value LIKE '%" . $filters['search'] . "%' 
                OR qty.qty LIKE '%" . $filters['search'] . "%' 
            "));
        }

        parent::_renderFiltersBefore();
    }

}
