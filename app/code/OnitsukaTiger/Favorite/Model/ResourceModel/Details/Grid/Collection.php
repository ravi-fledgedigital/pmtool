<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\Favorite\Model\ResourceModel\Details\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Session\SessionManagerInterface;
use OnitsukaTiger\Favorite\Helper\Data as DataHelper;
use Magento\Framework\App\RequestInterface;
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
        DataHelper $dataHelper,
        RequestInterface $request,
        SessionManagerInterface $coreSession,
        TimezoneInterface $localeDate,
        $resourceModel = \Magento\Wishlist\Model\ResourceModel\Item::class,
        $mainTable = 'wishlist_item'
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
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        
        $params = $this->request->getParams();
        $exclude = $this->dataHelper->getConfigFavorites();
        // $sizeId = $this->dataHelper->getAttributeId(DataHelper::SIZE_CODE, DataHelper::PRODUCT_TYPE_ID);
        $fkuId = !empty($params['id']) ? $params['id'] : $this->_coreSession->getFavoriteDetailsId();
        $fkuIdArr = explode("_", $fkuId);
        $id = $fkuIdArr[0];
        $jc = '"';

        $productType = !empty($params['type_id']) ? $params['type_id'] : $this->_coreSession->getFavoriteDetailsType();

        if (!$id){
            $id = 'null';
        }

        $wishlist = $this->dataHelper->getTableName('wishlist');
        $customer = $this->dataHelper->getTableName('customer_entity');
        $wishlistItemOption = $this->dataHelper->getTableName('wishlist_item_option');

        $this->getSelect()
            ->joinLeft(
                [
                    'wishlist' => $wishlist
                ],
                'main_table.wishlist_id = wishlist.wishlist_id',
                ['customer_id']
            )
            ->joinLeft(
                [
                    'customer' => $customer
                ],
                'wishlist.customer_id = customer.entity_id',
                ['CONCAT(customer.firstname, " ", customer.lastname) AS fullname','email']
            )
            ->joinLeft(
                [
                    'wishlist_item_option' => $wishlistItemOption
                ],
                'wishlist_item_option.wishlist_item_id = main_table.wishlist_item_id',
                []
            );

        if($productType === DataHelper::CONFIGURABLE_CODE && !empty($fkuIdArr[1])){
            $colorCode = $fkuIdArr[1];
            $this->getSelect()
                ->where('wishlist_item_option.code = ?', DataHelper::ATTRIBUTES)
                ->where(new \Zend_Db_Expr("(select value from eav_attribute_option_value where
                    option_id = json_unquote(json_extract(wishlist_item_option.value, concat('$.','".$jc."',json_unquote(json_extract(json_keys(wishlist_item_option.value), '$[0]')),'".$jc."'))) 
                    and store_id = (select store_id from store where code = 'default')) = ".$colorCode.""))     
                ->where('wishlist_item_option.product_id = ?', $id)
                ->where('json_length(wishlist_item_option.value) = 1');
        }else{
            $this->getSelect()
                ->where('wishlist_item_option.code = ?', DataHelper::SIMPLE_PRODUCT)
                ->where('wishlist_item_option.value = ?', $id)
                ->where('wishlist_item_option.wishlist_item_id NOT IN ('. $exclude .')');
        }

        $this->getSelect()->columns([
            "main_wishlist_item_id" => new \Zend_Db_Expr("main_table.wishlist_item_id"),
        ]);
        $this->getSelect()->group(new \Zend_Db_Expr('wishlist.customer_id'));

        //echo $this->getSelect();die;
        return $this;
    }

    public function addFilterMapColumn()
    {
        $columnsToMap = [
            [
                "alias"     => "fullname", 
                "fields"    => new \Zend_Db_Expr('CONCAT(customer.firstname, " ", customer.lastname)')
            ],
            [
                "alias"     => "main_wishlist_item_id", 
                "fields"    => new \Zend_Db_Expr('main_table.wishlist_item_id')
            ]
        ];

        foreach($columnsToMap as $columnIndex){
            $this->addFilterToMap(
                $columnIndex['alias'],
                $columnIndex['fields']
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'added_at') {
            if (is_array($condition)) {
                    foreach ($condition as $key => $value) {
                    if(!is_object($value) && (strpos($value, '-') !== false)){
                            $condition[$key] = $this->localeDate->convertConfigTimeToUtc($value);
                        }
                    }
            }
        }

        return parent::addFieldToFilter($field, $condition);
    }

}