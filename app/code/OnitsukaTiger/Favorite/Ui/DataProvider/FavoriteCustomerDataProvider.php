<?php
namespace OnitsukaTiger\Favorite\Ui\DataProvider;

use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Session\SessionManagerInterface;
use OnitsukaTiger\Favorite\Helper\Data as DataHelper;

class FavoriteCustomerDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    protected $request;
    protected $dataPersistor;
    protected $modifiersPool;
    protected $dataHelper;
	protected $_coreSession;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $wishlistItemFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $wishlistItemFactory,
        StoreManagerInterface $storeManager,
        DataPersistorInterface $dataPersistor,
        Http $request,        
        DataHelper $dataHelper,
        SessionManagerInterface $coreSession,
        array $meta = [],
        array $data = [],
        PoolInterface $modifiersPool = null
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->request = $request;
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        $this->wishlistItemFactory = $wishlistItemFactory;
        $this->modifiersPool = $modifiersPool ?: ObjectManager::getInstance()->get(PoolInterface::class);
        $this->dataHelper = $dataHelper;
        // $this->collection = $collection;
		$this->_coreSession = $coreSession;
        $this->initCollection();
        
    }

    public function initCollection()
    {
        $exclude = $this->dataHelper->getConfigFavorites();
        //$sizeId = $this->dataHelper->getAttributeId(DataHelper::SIZE_CODE, DataHelper::PRODUCT_TYPE_ID);
		$id = $this->_coreSession->getFavoriteDetailsId();
        $productType = $this->_coreSession->getFavoriteDetailsType();

        if (!$id){
            $id = 'null';
        }

        $wishlist = $this->dataHelper->getTableName('wishlist');
        $customer = $this->dataHelper->getTableName('customer_entity');
        $wishlistItemOption = $this->dataHelper->getTableName('wishlist_item_option');

        $collection = $this->wishlistItemFactory->create();
        $collection->getSelect()
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
                ['concat(customer.firstname, " ", customer.lastname) AS fullname','email']
            )
            ->joinLeft(
                [
                    'wishlist_item_option' => $wishlistItemOption
                ],
                'wishlist_item_option.wishlist_item_id = main_table.wishlist_item_id',
                ['wishlist_item_option.value AS id']
            );

        if($productType === DataHelper::CONFIGURABLE_CODE){
            $collection->getSelect()
                ->where('wishlist_item_option.code = ?', DataHelper::ATTRIBUTES)
                // ->where('wishlist_item_option.value NOT LIKE "%\"' . $sizeId . '\":%"')     
                ->where('wishlist_item_option.product_id = ?', $id);
        }else{
            $collection->getSelect()
                ->where('wishlist_item_option.code = ?', DataHelper::SIMPLE_PRODUCT)
                ->where('wishlist_item_option.value = ?', $id)
                ->where('wishlist_item_option.wishlist_item_id NOT IN (?)', $exclude);
        }

        $this->collection = $collection;
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
            if($filter->getField() == 'fullname'){
                $this->getCollection()->getSelect()->where('concat(customer.firstname, " ", customer.lastname) LIKE "%' . $filter->getValue() . '%"');
            }else{
                parent::addFilter($filter);
            }
        }
    }
}