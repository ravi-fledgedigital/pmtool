<?php
declare(strict_types=1);

namespace OnitsukaTiger\Restock\Block\Index;

use Magento\ProductAlert\Model\Stock;
use Magento\Customer\Model\Session;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class Restock extends \Magento\Wishlist\Block\Customer\Wishlist\Items
{

    protected $_stockFactory;
    protected $_customerSession;
    protected $_product;
    protected $_stockState;
    protected $_configurable;
    protected $_productCollection;
    protected $stockItemRepository;
    protected $httpContext;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Stock $stockFactory,
        Session $customerSession,
        ProductFactory $product,
        StockStateInterface $stockState,
        Configurable $configurable,
        ProductCollection $productCollection,
        StockRegistryInterface $stockItemRepository,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    ) {
        $this->_stockFactory = $stockFactory;
        $this->_customerSession = $customerSession;
        $this->_product = $product;
        $this->_stockState = $stockState;
        $this->_configurable = $configurable;
        $this->_productCollection = $productCollection;
        $this->_stockItemRepository = $stockItemRepository;
        $this->httpContext = $httpContext;
        parent::__construct($context, $data);
    }


    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getProductsWithAlerts()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'custom.restock.pager'
            )->setAvailableLimit([10 => 10, 15 => 15, 20 => 20])
                ->setShowPerPage(true)->setCollection(
                    $this->getProductsWithAlerts()
                );
            $this->setChild('pager', $pager);
            $this->getProductsWithAlerts()->load();
        }
        return $this;
    }
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    public function getProductsWithAlerts()
    {
        $page = ($this->getRequest()->getParam('p')) ? $this->getRequest()->getParam('p') : 1;
        $pageSize = ($this->getRequest()->getParam('limit')) ? $this->getRequest()->getParam('limit') : 10;

        $alertStocks = $this->_stockFactory->getCollection()
        ->addFieldToFilter('customer_id',$this->httpContext->getValue('customer_id'))
        ->addFieldToFilter('status',0)
        ->setPageSize($pageSize)
        ->setCurPage($page);

        return $alertStocks;
    }

    public function getProductById($id)
    {
        try {
            return $parentProduct = $this->_product->create()->load($id);
        }catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function getParentProductUrl($productId)
    {
        $parentId = $this->_configurable->getParentIdsByChild($productId);
        if (isset($parentId[0])) {
            $parentId = $parentId[0];
            $parentProduct = $this->_product->create()->load($parentId);
            
            return ($parentProduct->getStatus() == 1) ? $parentProduct->getProductUrl() : '#';
        }
        return '#';
    }

    public function getParentProductBrandLogo($productId){

        $parentId = $this->_configurable->getParentIdsByChild($productId);
        if (isset($parentId[0])) {
            $parentId = $parentId[0];
            $parentProduct = $this->_product->create()->load($parentId);
            
            return $parentProduct->getBrand();
        }

    }

    public function getStockItem($productId)
    {
        return $this->_stockItemRepository->getStockItem($productId);
    }
}

