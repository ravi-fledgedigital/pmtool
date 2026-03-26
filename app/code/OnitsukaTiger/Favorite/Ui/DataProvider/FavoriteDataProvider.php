<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\Favorite\Ui\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use OnitsukaTiger\Favorite\Helper\Data as DataHelper;
use Magento\Framework\App\Request\Http;

/**
 * Class FavoriteDataProvider
 *
 * @api
 * @since 100.0.2
 */
class FavoriteDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Product collection
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $collection;

    /**
     * @var \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]
     */
    protected $addFieldStrategies;

    /**
     * @var \Magento\Ui\DataProvider\AddFilterToCollectionInterface[]
     */
    protected $addFilterStrategies;

    /**
     * @var PoolInterface
     */
    private $modifiersPool;

    protected $dataHelper;

    protected $request;


    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Ui\DataProvider\AddFieldToCollectionInterface[] $addFieldStrategies
     * @param \Magento\Ui\DataProvider\AddFilterToCollectionInterface[] $addFilterStrategies
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $modifiersPool
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataHelper $dataHelper,
        Http $request,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = [],
        PoolInterface $modifiersPool = null
        
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->addFieldStrategies = $addFieldStrategies;
        $this->addFilterStrategies = $addFilterStrategies;
        $this->modifiersPool = $modifiersPool ?: ObjectManager::getInstance()->get(PoolInterface::class);
        $this->dataHelper = $dataHelper;
        $this->request = $request;
        $this->collection->setStoreId(Store::DEFAULT_STORE_ID);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $exclude = $this->dataHelper->getConfigFavorites();
        $sizeId = $this->dataHelper->getAttributeId(DataHelper::SIZE_CODE, DataHelper::PRODUCT_TYPE_ID);
        $cataloginventory = $this->dataHelper->getTableName('cataloginventory_stock_item');
        $params = $this->request->getParams();
        $where = '';
        $excludeSql = '';

        if(!empty($params['filters']['period']['from'])){
            $date = $this->dataHelper->formatDate($params['filters']['period']['from'], 'from');
            $where .= "AND wi.added_at >= '". $date ."'";
        }

        if(!empty($params['filters']['period']['to'])){
            $date = $this->dataHelper->formatDate($params['filters']['period']['to'], 'to');
            $where .= "AND wi.added_at <= '". $date ."'";
        }

        if(!empty($exclude)){
            $excludeSql = "AND wia.wishlist_item_id NOT IN (" . $exclude . ")";
        }

        $collection = $this->getCollection()
            ->addAttributeToSelect('*')
            ->joinField(
                'qty',
                $cataloginventory,
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );

        $collection->getSelect()->columns("(
            SELECT COUNT(*) 
            FROM wishlist_item as wi 
            INNER JOIN wishlist_item_option as wia 
            ON wi.wishlist_item_id = wia.wishlist_item_id 
            WHERE  (
                CASE
                WHEN e.type_id = '" . DataHelper::CONFIGURABLE_CODE . "' THEN (e.entity_id = wia.product_id AND wia.value NOT LIKE '%\"" . $sizeId . "\":%' AND wia.code = '" . DataHelper::ATTRIBUTES . "')
                ELSE (e.entity_id = wia.product_id AND wia.code = '" . DataHelper::SIMPLE_PRODUCT . "' " . $excludeSql . ")
                END
            ) " . $where . "
        ) as total_favorites");

        $collection->getSelect()->columns("(SUBSTRING_INDEX(e.sku, '_', 2)) as fku");

        if(!empty($params['sorting']['field']) && $params['sorting']['field'] == 'total_favorites'){
            $direction = $params['sorting']['direction'];
            $collection->getSelect()->order('total_favorites ' . $direction . '');
        }

        if (!$collection->isLoaded()) {
            $collection->load();
        }
        
        $items = $collection->toArray();

        $data = [
            'totalRecords' => $collection->getSize(),
            'items' => array_values($items),
        ];

        /** @var ModifierInterface $modifier */
        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }
        return $data;
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if(isset($this->addFilterStrategies[$filter->getField()])){
            $this->addFilterStrategies[$filter->getField()]
                ->addFilter(
                    $this->getCollection(),
                    $filter->getField(),
                    [$filter->getConditionType() => $filter->getValue()]
                );
        }else{
            if($filter->getField() == 'period'){
                // no action
            }else if($filter->getField() == 'fku'){
                $this->getCollection()->getSelect()->where('SUBSTRING_INDEX(e.sku, "_", 2) LIKE "%' . $filter->getValue() . '%"');
            }else{
                parent::addFilter($filter);
            }
        }
    }

    public function addOrder($field, $direction)
    {
        if($field == 'total_favorites'){
            // no action
        }else if($field == 'fku'){
            $this->getCollection()->addOrder('sku', $direction);
        }else{
            $this->getCollection()->addOrder($field, $direction);
        }
    }
}
